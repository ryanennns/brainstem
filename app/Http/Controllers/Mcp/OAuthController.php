<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    private const AUTHORIZATION_CODE_CACHE_PREFIX = 'brainstem.mcp.oauth.authorization-codes.';

    private const AUTHORIZATION_SESSION_KEY = 'brainstem.mcp.oauth.authorization';

    private const CLIENT_CACHE_PREFIX = 'brainstem.mcp.oauth.clients.';

    private const OAUTH_SCOPE = 'mcp:use';

    private const REFRESH_TOKEN_CACHE_PREFIX = 'brainstem.mcp.oauth.refresh-tokens.';

    public function protectedResource(Request $request): JsonResponse
    {
        $path = $request->route('path');
        $resource = is_string($path) && $path !== '' ? url('/'.$path) : url('/');

        return $this->noStore(response()->json([
            'resource' => $resource,
            'authorization_servers' => [$this->issuer()],
            'scopes_supported' => [self::OAUTH_SCOPE],
            'bearer_methods_supported' => ['header'],
        ]));
    }

    public function authorizationServer(): JsonResponse
    {
        return $this->noStore(response()->json([
            'issuer' => $this->issuer(),
            'authorization_endpoint' => route('mcp.oauth.authorize'),
            'token_endpoint' => route('mcp.oauth.token'),
            'registration_endpoint' => route('mcp.oauth.register'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'scopes_supported' => [self::OAUTH_SCOPE],
            'token_endpoint_auth_methods_supported' => ['none'],
        ]));
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'string'],
            'client_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'grant_types' => ['sometimes', 'array'],
            'grant_types.*' => ['string'],
            'response_types' => ['sometimes', 'array'],
            'response_types.*' => ['string'],
            'scope' => ['nullable', 'string'],
            'token_endpoint_auth_method' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->oauthError('invalid_client_metadata', $validator->errors()->first());
        }

        $data = $validator->validated();
        $redirectUris = array_values(array_unique($data['redirect_uris']));

        foreach ($redirectUris as $redirectUri) {
            if (! $this->isLoopbackRedirectUri($redirectUri)) {
                return $this->oauthError('invalid_redirect_uri', 'Redirect URIs must use localhost or loopback addresses.');
            }
        }

        $grantTypes = array_values(array_unique($data['grant_types'] ?? ['authorization_code', 'refresh_token']));

        if (! $this->containsOnly($grantTypes, ['authorization_code', 'refresh_token'])
            || ! in_array('authorization_code', $grantTypes, true)) {
            return $this->oauthError('invalid_client_metadata', 'Only authorization code and refresh token grants are supported.');
        }

        $responseTypes = array_values(array_unique($data['response_types'] ?? ['code']));

        if ($responseTypes !== ['code']) {
            return $this->oauthError('invalid_client_metadata', 'Only the code response type is supported.');
        }

        if (($data['token_endpoint_auth_method'] ?? 'none') !== 'none') {
            return $this->oauthError('invalid_client_metadata', 'Only public clients are supported.');
        }

        $scope = $this->scope($data['scope'] ?? null);

        if ($scope === null) {
            return $this->oauthError('invalid_scope', 'The requested scope is not supported.');
        }

        $clientId = Str::random(40);
        $clientName = $data['client_name'] ?? $data['name'] ?? 'MCP client';

        Cache::forever(self::CLIENT_CACHE_PREFIX.$clientId, [
            'client_id' => $clientId,
            'client_name' => $clientName,
            'redirect_uris' => $redirectUris,
            'grant_types' => $grantTypes,
            'response_types' => $responseTypes,
            'scope' => $scope,
            'token_endpoint_auth_method' => 'none',
        ]);

        return $this->noStore(response()->json([
            'client_id' => $clientId,
            'client_id_issued_at' => now()->timestamp,
            'client_name' => $clientName,
            'redirect_uris' => $redirectUris,
            'grant_types' => $grantTypes,
            'response_types' => $responseTypes,
            'scope' => $scope,
            'token_endpoint_auth_method' => 'none',
        ], 201));
    }

    public function authorize(Request $request): View|RedirectResponse|JsonResponse
    {
        $clientId = $request->string('client_id')->toString();
        $client = $this->client($clientId);

        if ($client === null) {
            return $this->oauthError('invalid_client', 'The client is not registered.');
        }

        $redirectUri = $request->string('redirect_uri')->toString();

        if (! in_array($redirectUri, $client['redirect_uris'], true)) {
            return $this->oauthError('invalid_request', 'The redirect URI is not registered.');
        }

        if ($request->string('response_type')->toString() !== 'code') {
            return $this->oauthError('unsupported_response_type', 'Only the code response type is supported.');
        }

        $scope = $this->scope($request->input('scope'));

        if ($scope === null) {
            return $this->oauthError('invalid_scope', 'The requested scope is not supported.');
        }

        $codeChallenge = $request->string('code_challenge')->toString();

        if (! $this->isValidCodeChallenge($codeChallenge)
            || $request->string('code_challenge_method')->toString() !== 'S256') {
            return $this->oauthError('invalid_request', 'S256 PKCE is required.');
        }

        if (! Auth::guard('web')->check()) {
            return redirect()->guest(route('login'));
        }

        $state = $request->input('state');
        $resource = $request->input('resource');
        $user = Auth::guard('web')->user();

        $authorizationToken = Str::random(80);

        $request->session()->put(self::AUTHORIZATION_SESSION_KEY, [
            'authorization_token' => $authorizationToken,
            'client_id' => $clientId,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'redirect_uri' => $redirectUri,
            'resource' => is_string($resource) ? $resource : null,
            'scope' => $scope,
            'state' => is_string($state) ? $state : null,
            'user_id' => $user->getAuthIdentifier(),
        ]);

        return view('oauth.authorize', [
            'authToken' => $authorizationToken,
            'client' => $client,
            'scopes' => [$scope],
            'user' => $user,
        ]);
    }

    public function approve(Request $request): RedirectResponse|JsonResponse
    {
        $pending = $request->session()->get(self::AUTHORIZATION_SESSION_KEY);

        if (! is_array($pending)) {
            return $this->oauthError('invalid_request', 'The authorization request has expired.');
        }

        if (! Auth::guard('web')->check()
            || (string) Auth::guard('web')->id() !== (string) ($pending['user_id'] ?? '')) {
            return $this->oauthError('access_denied', 'The signed-in user cannot approve this request.');
        }

        if (! hash_equals((string) ($pending['authorization_token'] ?? ''), $request->string('authorization_token')->toString())) {
            return $this->oauthError('invalid_request', 'The authorization request is invalid.');
        }

        $request->session()->forget(self::AUTHORIZATION_SESSION_KEY);

        if ($request->string('decision')->toString() !== 'approve') {
            return $this->redirectWithQuery($pending['redirect_uri'], [
                'error' => 'access_denied',
                ...$this->stateParameter($pending),
            ]);
        }

        $code = Str::random(80);

        Cache::put(self::AUTHORIZATION_CODE_CACHE_PREFIX.$this->hashToken($code), [
            'client_id' => $pending['client_id'],
            'code_challenge' => $pending['code_challenge'],
            'code_challenge_method' => $pending['code_challenge_method'],
            'redirect_uri' => $pending['redirect_uri'],
            'resource' => $pending['resource'],
            'scope' => $pending['scope'],
            'user_id' => $pending['user_id'],
        ], now()->addMinutes(5));

        return $this->redirectWithQuery($pending['redirect_uri'], [
            'code' => $code,
            ...$this->stateParameter($pending),
        ]);
    }

    public function token(Request $request): JsonResponse
    {
        $clientId = $request->string('client_id')->toString();
        $client = $this->client($clientId);

        if ($client === null) {
            return $this->oauthError('invalid_client', 'The client is not registered.');
        }

        $grantType = $request->string('grant_type')->toString();

        if ($grantType === 'authorization_code') {
            return $this->exchangeAuthorizationCode($request, $client, $clientId);
        }

        if ($grantType === 'refresh_token') {
            return $this->exchangeRefreshToken($request, $client, $clientId);
        }

        return $this->oauthError('unsupported_grant_type', 'The requested grant type is not supported.');
    }

    private function exchangeAuthorizationCode(Request $request, array $client, string $clientId): JsonResponse
    {
        $code = $request->string('code')->toString();
        $payload = $code === ''
            ? null
            : Cache::pull(self::AUTHORIZATION_CODE_CACHE_PREFIX.$this->hashToken($code));

        if (! is_array($payload)
            || ($payload['client_id'] ?? null) !== $clientId
            || $request->string('redirect_uri')->toString() !== ($payload['redirect_uri'] ?? null)) {
            return $this->oauthError('invalid_grant', 'The authorization code is invalid or expired.');
        }

        $codeVerifier = $request->string('code_verifier')->toString();

        if (! $this->matchesCodeChallenge($codeVerifier, $payload['code_challenge'] ?? null)) {
            return $this->oauthError('invalid_grant', 'The PKCE verifier is invalid.');
        }

        return $this->issueTokens($client, $payload);
    }

    private function exchangeRefreshToken(Request $request, array $client, string $clientId): JsonResponse
    {
        if (! in_array('refresh_token', $client['grant_types'], true)) {
            return $this->oauthError('unauthorized_client', 'The client is not allowed to use refresh tokens.');
        }

        $refreshToken = $request->string('refresh_token')->toString();
        $payload = $refreshToken === ''
            ? null
            : Cache::pull(self::REFRESH_TOKEN_CACHE_PREFIX.$this->hashToken($refreshToken));

        if (! is_array($payload) || ($payload['client_id'] ?? null) !== $clientId) {
            return $this->oauthError('invalid_grant', 'The refresh token is invalid or expired.');
        }

        return $this->issueTokens($client, $payload);
    }

    private function issueTokens(array $client, array $payload): JsonResponse
    {
        $userId = $payload['user_id'] ?? null;
        $user = $userId === null ? null : User::query()->find($userId);

        if ($user === null) {
            return $this->oauthError('invalid_grant', 'The user associated with this grant no longer exists.');
        }

        $expiresAt = now()->addYear();
        $accessToken = $user->createToken('mcp-oauth', [self::OAUTH_SCOPE], $expiresAt);
        $response = [
            'access_token' => $accessToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) now()->diffInSeconds($expiresAt),
            'scope' => self::OAUTH_SCOPE,
        ];

        if (in_array('refresh_token', $client['grant_types'], true)) {
            $refreshToken = Str::random(80);

            Cache::put(self::REFRESH_TOKEN_CACHE_PREFIX.$this->hashToken($refreshToken), [
                'client_id' => $client['client_id'],
                'scope' => self::OAUTH_SCOPE,
                'user_id' => $user->getKey(),
            ], now()->addDays(30));

            $response['refresh_token'] = $refreshToken;
        }

        return $this->noStore(response()->json($response));
    }

    private function client(string $clientId): ?array
    {
        $client = Cache::get(self::CLIENT_CACHE_PREFIX.$clientId);

        return is_array($client) ? $client : null;
    }

    private function containsOnly(array $values, array $allowed): bool
    {
        return count(array_diff($values, $allowed)) === 0;
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function isLoopbackRedirectUri(string $redirectUri): bool
    {
        $parts = parse_url($redirectUri);

        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['fragment'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'], '[]'));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function isValidCodeChallenge(string $codeChallenge): bool
    {
        return preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $codeChallenge) === 1;
    }

    private function issuer(): string
    {
        return (string) (config('mcp.authorization_server') ?: url('/'));
    }

    private function matchesCodeChallenge(string $codeVerifier, mixed $codeChallenge): bool
    {
        if (! preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $codeVerifier)
            || ! is_string($codeChallenge)) {
            return false;
        }

        $challenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        return hash_equals($codeChallenge, $challenge);
    }

    private function noStore(JsonResponse $response): JsonResponse
    {
        return $response->header('Cache-Control', 'no-store');
    }

    private function oauthError(string $error, string $description, int $status = 400): JsonResponse
    {
        return $this->noStore(response()->json([
            'error' => $error,
            'error_description' => $description,
        ], $status));
    }

    private function redirectWithQuery(string $redirectUri, array $parameters): RedirectResponse
    {
        $separator = str_contains($redirectUri, '?') ? '&' : '?';
        $location = $redirectUri.$separator.http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($location);
    }

    private function scope(mixed $scope): ?string
    {
        if ($scope === null || (is_string($scope) && trim($scope) === '')) {
            return self::OAUTH_SCOPE;
        }

        if (! is_string($scope)) {
            return null;
        }

        $scopes = preg_split('/\s+/', trim($scope), -1, PREG_SPLIT_NO_EMPTY);

        return $scopes === [self::OAUTH_SCOPE] ? self::OAUTH_SCOPE : null;
    }

    private function stateParameter(array $pending): array
    {
        return isset($pending['state']) && is_string($pending['state'])
            ? ['state' => $pending['state']]
            : [];
    }
}
