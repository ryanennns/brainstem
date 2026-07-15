<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_whitelisted_users_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create(['email' => 'ryanennns@gmail.com']);
        $user = User::factory()->create(['email' => 'not-admin@example.com']);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
