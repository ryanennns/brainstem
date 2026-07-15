<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProjectServer;
use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\CreateProjectUpdate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_and_updates_can_be_created(): void
    {
        $user = User::factory()->create();

        ProjectServer::actingAs($user)->tool(CreateProject::class, [
            'name' => 'Brainstem',
            'description' => 'Agent project',
        ])->assertOk();

        $project = Project::query()->firstOrFail();

        ProjectServer::actingAs($user)->tool(CreateProjectUpdate::class, [
            'project_id' => $project->getKey(),
            'type' => 'code_change',
            'summary' => 'Added MCP tools.',
        ])->assertOk();

        $this->assertDatabaseHas('projects', ['user_id' => $user->getKey()]);
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
}
