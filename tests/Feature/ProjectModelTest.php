<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_git_branches_are_cast_to_an_array(): void
    {
        $project = Project::query()->create([
            'name' => 'Brainstem',
            'user_id' => User::factory()->create()->getKey(),
            'git_branches' => ['main', 'feature/mcp'],
        ]);

        $this->assertSame(['main', 'feature/mcp'], $project->fresh()->git_branches);
    }
}
