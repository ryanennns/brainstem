<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_belong_to_repositories_and_cast_working_branches(): void
    {
        $user = User::factory()->create();
        $repository = Repository::query()->create([
            'name' => 'brainstem',
            'user_id' => $user->getKey(),
        ]);
        $project = Project::query()->create([
            'name' => 'Brainstem',
            'user_id' => $user->getKey(),
            'repository_id' => $repository->getKey(),
            'working_branches' => ['feature/mcp'],
        ]);

        $project = Project::query()->with('repository')->findOrFail($project->getKey());

        $this->assertSame(['feature/mcp'], $project->working_branches);
        $this->assertSame($repository->getKey(), $project->repository->getKey());
        $this->assertSame($project->getKey(), Project::query()
            ->where('repository_id', $repository->getKey())
            ->firstOrFail()
            ->getKey());
    }
}
