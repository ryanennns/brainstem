<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProjectServer;
use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\CreateProjectUpdate;
use App\Mcp\Tools\GetProject;
use App\Mcp\Tools\GetProjectUpdates;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\SearchProjects;
use App\Mcp\Tools\UpdateProject;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_and_updates_can_be_created(): void
    {
        $user = User::factory()->create();
        $repository = Repository::query()->create([
            'name' => 'brainstem',
            'default_branch' => 'main',
            'user_id' => $user->getKey(),
        ]);

        ProjectServer::actingAs($user)->tool(CreateProject::class, [
            'name' => 'Brainstem',
            'description' => 'Agent project',
            'repository_id' => $repository->getKey(),
            'working_branches' => ['feature/mcp'],
        ])->assertOk();

        $project = Project::query()->firstOrFail();

        ProjectServer::actingAs($user)->tool(CreateProjectUpdate::class, [
            'project_id' => $project->getKey(),
            'type' => 'code_change',
            'summary' => 'Added MCP tools.',
        ])->assertOk();

        $this->assertDatabaseHas('projects', ['user_id' => $user->getKey()]);
        $project = Project::query()->with('repository')->findOrFail($project->getKey());
        $this->assertSame(['feature/mcp'], $project->working_branches);
        $this->assertSame($repository->getKey(), $project->repository->getKey());
        $this->assertDatabaseHas('project_updates', ['project_id' => $project->getKey()]);
    }

    public function test_updates_cannot_be_created_for_another_users_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::query()->create([
            'name' => 'Private',
            'user_id' => $owner->getKey(),
        ]);

        ProjectServer::actingAs(User::factory()->create())->tool(CreateProjectUpdate::class, [
            'project_id' => $project->getKey(),
            'type' => 'miscellaneous',
            'summary' => 'Not allowed.',
        ])->assertHasErrors(['Project not found.']);

        $this->assertDatabaseCount('project_updates', 0);
    }

    public function test_projects_can_be_listed_and_retrieved(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'name' => 'Visible',
            'user_id' => $user->getKey(),
        ]);
        Project::query()->create([
            'name' => 'Hidden',
            'user_id' => User::factory()->create()->getKey(),
        ]);

        ProjectServer::actingAs($user)->tool(ListProjects::class)
            ->assertOk()
            ->assertSee('Visible');

        ProjectServer::actingAs($user)->tool(GetProject::class, [
            'project_id' => $project->getKey(),
        ])->assertOk()
            ->assertSee('Visible');
    }

    public function test_projects_cannot_be_retrieved_by_another_user(): void
    {
        $project = Project::query()->create([
            'name' => 'Private',
            'user_id' => User::factory()->create()->getKey(),
        ]);

        ProjectServer::actingAs(User::factory()->create())->tool(GetProject::class, [
            'project_id' => $project->getKey(),
        ])->assertHasErrors(['Project not found.']);
    }

    public function test_projects_can_be_searched_by_name(): void
    {
        $user = User::factory()->create();
        Project::query()->create(['name' => 'Brainstem', 'user_id' => $user->getKey()]);
        Project::query()->create(['name' => 'Unrelated', 'user_id' => $user->getKey()]);

        ProjectServer::actingAs($user)->tool(SearchProjects::class, ['query' => 'brain'])
            ->assertOk()
            ->assertSee('Brainstem');
    }

    public function test_projects_can_update_their_details_repository_and_working_branches(): void
    {
        $user = User::factory()->create();
        $repository = Repository::query()->create([
            'name' => 'brainstem',
            'user_id' => $user->getKey(),
        ]);
        $project = Project::query()->create([
            'name' => 'Before',
            'description' => 'Before description',
            'user_id' => $user->getKey(),
        ]);

        ProjectServer::actingAs($user)->tool(UpdateProject::class, [
            'project_id' => $project->getKey(),
            'name' => 'After',
            'description' => 'After description',
            'repository_id' => $repository->getKey(),
            'working_branches' => ['feature/mcp'],
        ])->assertOk()
            ->assertSee('After');

        $this->assertDatabaseHas('projects', [
            'id' => $project->getKey(),
            'name' => 'After',
            'description' => 'After description',
            'user_id' => $user->getKey(),
        ]);
        $project = Project::query()->findOrFail($project->getKey());
        $this->assertSame(['feature/mcp'], $project->working_branches);
        $this->assertSame($repository->getKey(), $project->repository_id);
    }

    public function test_projects_cannot_use_another_users_repository(): void
    {
        $repository = Repository::query()->create([
            'name' => 'Private',
            'user_id' => User::factory()->create()->getKey(),
        ]);

        ProjectServer::actingAs(User::factory()->create())->tool(CreateProject::class, [
            'name' => 'Not allowed',
            'repository_id' => $repository->getKey(),
        ])->assertHasErrors(['Repository not found.']);

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_updates_can_be_retrieved(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'name' => 'Brainstem',
            'user_id' => $user->getKey(),
        ]);
        ProjectUpdate::query()->create([
            'project_id' => $project->getKey(),
            'type' => 'code_change',
            'summary' => 'Added update retrieval.',
        ]);

        ProjectServer::actingAs($user)->tool(GetProjectUpdates::class, [
            'project_id' => $project->getKey(),
        ])->assertOk()
            ->assertSee('Added update retrieval.');
    }
}
