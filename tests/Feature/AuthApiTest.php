<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_sign_up_and_receive_an_api_token(): void
    {
        $response = $this->postJson('/api/sign-up', [
            'name' => 'Ryan',
            'email' => 'ryanenns@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()
            ->assertJsonPath('user.email', 'ryanenns@gmail.com')
            ->assertJsonMissingPath('user.password');

        $this->withToken($response->json('token'))
            ->getJson('/api/projects')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_users_can_sign_in_and_receive_an_api_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/sign-in', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.id', $user->getKey());

        $this->withToken($response->json('token'))
            ->getJson('/api/projects')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_sign_in_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/sign-in', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sign_up_rejects_emails_outside_the_whitelist(): void
    {
        $this->postJson('/api/sign-up', [
            'name' => 'Not Ryan',
            'email' => 'not-ryan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 0);
    }
}
