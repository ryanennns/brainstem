<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('registration.admin_password');

        if (! is_string($password) || $password === '') {
            throw new \LogicException('Set ADMIN_PASSWORD before seeding the administrator.');
        }

        User::query()->updateOrCreate(['email' => 'ryanennns@gmail.com'], [
            'name' => 'Ryan Enns',
            'password' => Hash::make($password),
        ]);
    }
}
