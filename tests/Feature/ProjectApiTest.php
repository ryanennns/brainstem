<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Repository;
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
        $repository = Repository::query()->create([
            'name' => 'brainstem',
            'remote_url' => 'git@github.com:ryanennns/brainstem.git',
            'default_branch' => 'main',
            'user_id' => $user->getKey(),
        ]);

        $created = $this->postJson('/api/projects', [
            'name' => 'Brainstem',
            'description' => 'A project',
            'repository_id' => $repository->getKey(),
            'working_branches' => ['feature/repositories'],
        ])->assertCreated()
            ->assertJsonPath('name', 'Brainstem')
            ->assertJsonPath('user_id', $user->getKey())
            ->assertJsonPath('repository.name', 'brainstem')
            ->assertJsonPath('working_branches.0', 'feature/repositories');

        $id = $created->json('id');

        $this->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.repository.id', $repository->getKey())
            ->assertJsonPath('total', 1);

        $this->getJson("/api/projects/{$id}")
            ->assertOk()
            ->assertJsonPath('description', 'A project')
            ->assertJsonPath('repository.default_branch', 'main');

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

    public function test_projects_cannot_use_another_users_repository(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $repository = Repository::query()->create([
            'name' => 'Private',
            'user_id' => User::factory()->create()->getKey(),
        ]);

        $this->postJson('/api/projects', [
            'name' => 'Not allowed',
            'repository_id' => $repository->getKey(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('repository_id');
    }
}
