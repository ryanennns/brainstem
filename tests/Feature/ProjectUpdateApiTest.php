<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_updates_can_be_created_and_read(): void
    {
        $project = Project::query()->create(['name' => 'Brainstem']);

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
        $this->postJson('/api/project-updates', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id', 'type', 'summary']);
    }
}
