<?php

namespace Tests\Feature;

use App\Ai\Agents\ProjectSummarizer;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\UpdatesRelationManager;
use App\Livewire\ProjectSummary;
use App\Models\Project;
use App\Models\ProjectAgentSession;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_whitelisted_users_can_access_the_admin_panel(): void
    {
        $admin = User::factory()->create(['email' => 'ryanennns@gmail.com']);
        $user = User::factory()->create(['email' => 'not-admin@example.com']);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/repositories')->assertOk();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_administrators_can_view_projects_and_project_updates(): void
    {
        Cache::flush();
        ProjectSummarizer::fake([
            'The admin panel is now available.',
            'The admin panel and cached AI summaries are now available.',
        ]);
        $admin = User::factory()->create(['email' => 'ryanennns@gmail.com']);
        $project = Project::query()->create([
            'name' => 'Brainstem',
            'description' => 'An agent project tracker.',
            'user_id' => $admin->getKey(),
        ]);
        $agentSession = ProjectAgentSession::query()->create([
            'project_id' => $project->getKey(),
            'agent' => 'codex',
            'session_id' => 'session-123',
        ]);
        $update = ProjectUpdate::query()->create([
            'project_id' => $project->getKey(),
            'project_agent_session_id' => $agentSession->getKey(),
            'type' => 'code_change',
            'summary' => 'Added the admin panel.',
        ]);

        $this->actingAs($admin)
            ->get("/admin/projects/{$project->getKey()}")
            ->assertOk()
            ->assertSee('Brainstem')
            ->assertSee('An agent project tracker.')
            ->assertSee('Generating summary')
            ->assertDontSee('The admin panel is now available.');

        ProjectSummarizer::assertNeverPrompted();

        Livewire::test(ProjectSummary::class, ['record' => $project])
            ->assertSee('The admin panel is now available.');
        Livewire::test(ProjectSummary::class, ['record' => $project])
            ->assertSee('The admin panel is now available.');

        ProjectUpdate::query()->create([
            'project_id' => $project->getKey(),
            'project_agent_session_id' => $agentSession->getKey(),
            'type' => 'code_change',
            'summary' => 'Added cached AI summaries.',
        ]);

        Livewire::test(ProjectSummary::class, ['record' => $project])
            ->assertSee('The admin panel and cached AI summaries are now available.');
        Livewire::test(UpdatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])->assertCanSeeTableRecords([$update]);
        $this->actingAs($admin)->get("/admin/project-updates/{$update->getKey()}")->assertOk();
    }

    public function test_projects_without_updates_do_not_prompt_ai(): void
    {
        ProjectSummarizer::fake();
        $admin = User::factory()->create(['email' => 'ryanennns@gmail.com']);
        $project = Project::query()->create([
            'name' => 'Empty project',
            'user_id' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->get("/admin/projects/{$project->getKey()}")
            ->assertOk()
            ->assertSee('Generating summary');

        ProjectSummarizer::assertNeverPrompted();

        Livewire::test(ProjectSummary::class, ['record' => $project])
            ->assertSee('No project updates yet.');

        ProjectSummarizer::assertNeverPrompted();
    }
}
