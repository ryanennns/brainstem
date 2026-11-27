<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device' => fake()->word().'-workstation',
            'source' => 'codex',
            'session_id' => fake()->uuid(),
            'summary' => "Goal: Review the failing tests.\nOutcome: Fixed the failure and verified the suite.",
        ];
    }
}
