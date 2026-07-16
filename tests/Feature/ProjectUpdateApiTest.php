<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectAgentSession;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_updates_can_be_created_and_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $project = Project::query()->create(['name' => 'Brainstem', 'user_id' => $user->getKey()]);

        $created = $this->postJson('/api/project-updates', [
            'project_id' => $project->getKey(),
            'agent' => 'codex',
            'agent_session_id' => 'session-123',
            'type' => 'code_change',
            'summary' => 'Added project update endpoints.',
        ])->assertCreated()
            ->assertJsonPath('project_id', $project->getKey())
            ->assertJsonPath('agent_session.agent', 'codex')
            ->assertJsonPath('agent_session.session_id', 'session-123');

        $id = $created->json('id');

        $this->getJson('/api/project-updates')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('total', 1);

        $this->getJson("/api/project-updates/{$id}")
            ->assertOk()
            ->assertJsonPath('summary', 'Added project update endpoints.');

    }

    public function test_project_update_creation_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/project-updates', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id', 'agent', 'agent_session_id', 'type', 'summary']);
    }

    public function test_project_updates_cannot_be_created_for_another_users_project(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::query()->create([
            'name' => 'Someone else\'s project',
            'user_id' => User::factory()->create()->getKey(),
        ]);

        $this->postJson('/api/project-updates', [
            'project_id' => $project->getKey(),
            'agent' => 'codex',
            'agent_session_id' => 'session-123',
            'type' => 'code_change',
            'summary' => 'Not allowed.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }

    public function test_project_updates_require_a_session_from_the_same_project(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'One', 'user_id' => $user->getKey()]);
        $otherProject = Project::query()->create(['name' => 'Two', 'user_id' => $user->getKey()]);
        $agentSession = ProjectAgentSession::query()->create([
            'project_id' => $otherProject->getKey(),
            'agent' => 'codex',
            'session_id' => 'session-123',
        ]);

        $this->expectException(QueryException::class);

        ProjectUpdate::query()->create([
            'project_id' => $project->getKey(),
            'project_agent_session_id' => $agentSession->getKey(),
            'type' => 'code_change',
            'summary' => 'Invalid session.',
        ]);
    }
}
