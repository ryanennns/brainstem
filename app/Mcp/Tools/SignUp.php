<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create an account and return a Sanctum API token for the project server.')]
class SignUp extends Tool
{
    public function handle(Request $request): Response
    {
        $user = User::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]));

        return Response::json([
            'user' => $user,
            'token' => $user->createToken('mcp')->plainTextToken,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The account name.')
                ->required(),
            'email' => $schema->string()
                ->description('The lowercase account email address.')
                ->required(),
            'password' => $schema->string()
                ->description('A password with at least eight characters.')
                ->required(),
            'password_confirmation' => $schema->string()
                ->description('The same password again.')
                ->required(),
        ];
    }
}
