<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sign in and return a Sanctum API token for the project server.')]
class SignIn extends Tool
{
    public function handle(Request $request): Response
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'lowercase'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        return Response::json([
            'user' => $user,
            'token' => $user->createToken('mcp')->plainTextToken,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'email' => $schema->string()
                ->description('The lowercase account email address.')
                ->required(),
            'password' => $schema->string()
                ->description('The account password.')
                ->required(),
        ];
    }
}
