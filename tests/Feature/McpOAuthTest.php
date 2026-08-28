<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class McpOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_oauth_metadata_describes_the_mcp_server(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp/projects')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp/projects'))
            ->assertJsonPath('authorization_servers.0', url('/'))
            ->assertJsonPath('scopes_supported.0', 'mcp:use');

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', url('/'))
            ->assertJsonPath('authorization_endpoint', route('mcp.oauth.authorize'))
            ->assertJsonPath('token_endpoint', route('mcp.oauth.token'))
            ->assertJsonPath('registration_endpoint', route('mcp.oauth.register'))
            ->assertJsonPath('code_challenge_methods_supported.0', 'S256');
    }

    public function test_dynamic_client_registration_rejects_non_loopback_redirect_uris(): void
    {
        $this->postJson('/oauth/register', [
            'redirect_uris' => ['https://example.com/callback'],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_redirect_uri');
    }

    public function test_codex_can_use_a_loopback_host_alias_for_a_dynamic_callback_path(): void
    {
        $user = User::factory()->create();
        $registeredRedirectUri = 'http://127.0.0.1:42569/callback/Gkj-qI-HxyO4';
        $authorizationRedirectUri = 'http://localhost:42569/callback/Gkj-qI-HxyO4';
        $client = $this->registerClient($registeredRedirectUri);
        $codeVerifier = Str::random(64);
        $authorizationUrl = route('mcp.oauth.authorize').'?'.http_build_query([
            'client_id' => $client['client_id'],
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
            'redirect_uri' => $authorizationRedirectUri,
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'test-state',
        ], '', '&', PHP_QUERY_RFC3986);

        $this->actingAs($user, 'web');
        $consent = $this->get($authorizationUrl)->assertOk();

        $authorization = $this->post(route('mcp.oauth.approve'), [
            'authorization_token' => $consent->viewData('authToken'),
            'decision' => 'approve',
        ])->assertRedirect();

        $location = $authorization->headers->get('Location');
        $this->assertStringStartsWith($authorizationRedirectUri.'?', (string) $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $authorizationParameters);

        $this->assertSame('test-state', $authorizationParameters['state']);

        $this->post(route('mcp.oauth.token'), [
            'client_id' => $client['client_id'],
            'code' => $authorizationParameters['code'],
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $registeredRedirectUri,
        ])->assertOk();
    }

    public function test_codex_can_complete_pkce_flow_and_call_the_project_server(): void
    {
        $user = User::factory()->create();
        $client = $this->registerClient();
        $codeVerifier = Str::random(64);
        $codeChallenge = $this->codeChallenge($codeVerifier);
        $authorizationUrl = route('mcp.oauth.authorize').'?'.http_build_query([
            'client_id' => $client['client_id'],
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'redirect_uri' => $client['redirect_uris'][0],
            'resource' => url('/mcp/projects'),
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'test-state',
        ], '', '&', PHP_QUERY_RFC3986);

        $this->get($authorizationUrl)
            ->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($authorizationUrl);

        $consent = $this->get($authorizationUrl)
            ->assertOk()
            ->assertSee('Authorize MCP client');

        $authorizationToken = $consent->viewData('authToken');

        $authorization = $this->post(route('mcp.oauth.approve'), [
            'authorization_token' => $authorizationToken,
            'decision' => 'approve',
        ])->assertRedirect();

        $location = $authorization->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $authorizationParameters);

        $this->assertSame('test-state', $authorizationParameters['state']);
        $this->assertArrayHasKey('code', $authorizationParameters);

        $token = $this->post(route('mcp.oauth.token'), [
            'client_id' => $client['client_id'],
            'code' => $authorizationParameters['code'],
            'code_verifier' => $codeVerifier,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $client['redirect_uris'][0],
        ])
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'refresh_token',
                'scope',
                'token_type',
            ]);

        $this->withToken($token->json('access_token'))
            ->postJson('/mcp/projects', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'capabilities' => [],
                    'clientInfo' => [
                        'name' => 'test-client',
                        'version' => '1.0.0',
                    ],
                    'protocolVersion' => '2025-03-26',
                ],
            ])
            ->assertOk();

        $refreshedToken = $this->post(route('mcp.oauth.token'), [
            'client_id' => $client['client_id'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->json('refresh_token'),
        ])
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
            ]);

        $this->assertNotSame($token->json('access_token'), $refreshedToken->json('access_token'));
    }

    public function test_authorization_codes_require_the_pkce_verifier(): void
    {
        $user = User::factory()->create();
        $client = $this->registerClient();
        $codeVerifier = Str::random(64);
        $authorizationUrl = route('mcp.oauth.authorize').'?'.http_build_query([
            'client_id' => $client['client_id'],
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
            'redirect_uri' => $client['redirect_uris'][0],
            'response_type' => 'code',
            'scope' => 'mcp:use',
        ], '', '&', PHP_QUERY_RFC3986);

        $this->actingAs($user, 'web');
        $consent = $this->get($authorizationUrl)->assertOk();

        $authorization = $this->post(route('mcp.oauth.approve'), [
            'authorization_token' => $consent->viewData('authToken'),
            'decision' => 'approve',
        ])->assertRedirect();

        $location = $authorization->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $authorizationParameters);

        $this->post(route('mcp.oauth.token'), [
            'client_id' => $client['client_id'],
            'code' => $authorizationParameters['code'],
            'code_verifier' => 'wrong-verifier',
            'grant_type' => 'authorization_code',
            'redirect_uri' => $client['redirect_uris'][0],
        ])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_grant');
    }

    private function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

    private function registerClient(string $redirectUri = 'http://127.0.0.1:43123/callback'): array
    {
        return $this->postJson('/oauth/register', [
            'client_name' => 'Codex',
            'redirect_uris' => [$redirectUri],
        ])
            ->assertCreated()
            ->json();
    }
}
