<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_administrator(): void
    {
        config()->set('registration.admin_password', 'password');

        $this->seed();

        $admin = User::query()->where('email', 'ryanennns@gmail.com')->firstOrFail();

        $this->assertSame('Ryan Enns', $admin->name);
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
