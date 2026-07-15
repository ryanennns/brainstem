<?php

namespace Tests\Feature;

use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\UpdatesRelationManager;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_administrators_can_view_projects_and_project_updates(): void
    {
        $admin = User::factory()->create(['email' => 'ryanennns@gmail.com']);
        $project = Project::query()->create([
            'name' => 'Brainstem',
            'user_id' => $admin->getKey(),
        ]);
        $update = ProjectUpdate::query()->create([
            'project_id' => $project->getKey(),
            'type' => 'code_change',
            'summary' => 'Added the admin panel.',
        ]);

        $this->actingAs($admin)->get("/admin/projects/{$project->getKey()}")->assertOk();
        Livewire::test(UpdatesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])->assertCanSeeTableRecords([$update]);
        $this->actingAs($admin)->get("/admin/project-updates/{$update->getKey()}")->assertOk();
    }
}
