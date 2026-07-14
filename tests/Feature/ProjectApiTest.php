<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_can_be_created_read_and_deleted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $created = $this->postJson('/api/projects', [
            'name' => 'Brainstem',
            'description' => 'A project',
        ])->assertCreated()
            ->assertJsonPath('name', 'Brainstem')
            ->assertJsonPath('user_id', $user->getKey());

        $id = $created->json('id');

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('total', 1);

        $this->getJson("/api/projects/{$id}")
            ->assertOk()
            ->assertJsonPath('description', 'A project');

        $this->deleteJson("/api/projects/{$id}")->assertNoContent();
        $this->assertDatabaseMissing(Project::class, ['id' => $id]);
    }

    public function test_project_creation_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/projects', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }
}
