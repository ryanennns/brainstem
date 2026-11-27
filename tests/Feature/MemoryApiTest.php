<?php

namespace Tests\Feature;

use App\Models\Memory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MemoryApiTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('sources')]
    public function test_it_records_completed_sessions(string $source): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('session-end-hook')->plainTextToken;
        $memory = Memory::factory()->raw([
            'user_id' => User::factory()->create()->getKey(),
            'source' => $source,
            'session_id' => 'session_non-uuid-123',
        ]);

        $this->withToken($token)->postJson('/api/memories', $memory)->assertNoContent();

        $this->assertDatabaseHas('memories', array_replace($memory, ['user_id' => $user->getKey()]));
    }

    public function test_repeated_completion_updates_the_same_memory_without_affecting_other_users_or_sources(): void
    {
        $user = User::factory()->create();
        $existing = Memory::factory()->create(['user_id' => $user->getKey()]);
        $otherSource = Memory::factory()->create([
            'user_id' => $user->getKey(),
            'source' => 'ignition',
            'session_id' => $existing->session_id,
        ]);
        $otherUser = Memory::factory()->create(['session_id' => $existing->session_id]);
        $payload = [
            'device' => 'new-workstation',
            'source' => $existing->source,
            'session_id' => $existing->session_id,
            'summary' => "Goal: Review the failing tests.\nOutcome: Fixed a second failure after resuming.",
        ];

        $this->withToken($user->createToken('session-end-hook')->plainTextToken)
            ->postJson('/api/memories', $payload)
            ->assertNoContent();

        $this->assertDatabaseCount('memories', 3);
        $this->assertDatabaseHas('memories', $payload + ['id' => $existing->getKey()]);
        $this->assertDatabaseHas('memories', ['id' => $otherSource->getKey(), 'summary' => $otherSource->summary]);
        $this->assertDatabaseHas('memories', ['id' => $otherUser->getKey(), 'summary' => $otherUser->summary]);
    }

    public function test_a_summary_requires_authentication_and_valid_session_metadata(): void
    {
        $this->postJson('/api/memories')->assertUnauthorized();

        $user = User::factory()->create();
        $this->withToken($user->createToken('session-end-hook')->plainTextToken)
            ->postJson('/api/memories', ['source' => 'unsupported', 'summary' => '  '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device', 'source', 'session_id', 'summary']);

        $this->assertDatabaseEmpty('memories');
    }

    public static function sources(): array
    {
        return [
            'Claude Code' => ['claude_code'],
            'Codex' => ['codex'],
            'OpenCode' => ['opencode'],
            'Ignition' => ['ignition'],
        ];
    }
}
