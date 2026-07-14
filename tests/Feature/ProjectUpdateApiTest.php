<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
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
        $project = Project::query()->create(['name' => 'Brainstem', 'user_id' => $user->id]);

        $created = $this->postJson('/api/project-updates', [
            'project_id' => $project->id,
            'type' => 'code_change',
            'summary' => 'Added project update endpoints.',
        ])->assertCreated()
            ->assertJsonPath('project_id', $project->id);

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
            ->assertJsonValidationErrors(['project_id', 'type', 'summary']);
    }

    public function test_project_updates_cannot_be_created_for_another_users_project(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $project = Project::query()->create([
            'name' => 'Someone else\'s project',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->postJson('/api/project-updates', [
            'project_id' => $project->id,
            'type' => 'code_change',
            'summary' => 'Not allowed.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }
}
