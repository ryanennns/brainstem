<?php

namespace Tests\Feature;

use App\Mcp\Servers\AuthServer;
use App\Mcp\Tools\SignIn;
use App\Mcp\Tools\SignUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_sign_up_and_receive_a_token(): void
    {
        AuthServer::tool(SignUp::class, [
            'name' => 'Ryan',
            'email' => 'ryan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'ryan@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_users_can_sign_in_and_receive_a_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        AuthServer::tool(SignIn::class, [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
