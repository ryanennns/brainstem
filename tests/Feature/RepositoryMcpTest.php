<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProjectServer;
use App\Mcp\Tools\CreateRepository;
use App\Mcp\Tools\GetRepository;
use App\Mcp\Tools\ListRepositories;
use App\Mcp\Tools\SearchRepositories;
use App\Mcp\Tools\UpdateRepository;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepositoryMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_repositories_can_be_created_listed_searched_retrieved_and_updated(): void
    {
        $user = User::factory()->create();

        ProjectServer::actingAs($user)->tool(CreateRepository::class, [
            'name' => 'Brainstem',
            'remote_url' => 'git@github.com:ryanennns/brainstem.git',
            'default_branch' => 'main',
        ])->assertOk();

        ProjectServer::actingAs($user)->tool(CreateRepository::class, [
            'name' => 'Duplicate',
            'remote_url' => 'git@github.com:ryanennns/brainstem.git',
            'default_branch' => 'develop',
        ])->assertOk()
            ->assertSee('Brainstem');

        $repository = Repository::query()->firstOrFail();

        $this->assertDatabaseCount('repositories', 1);

        ProjectServer::actingAs($user)->tool(ListRepositories::class)
            ->assertOk()
            ->assertSee('Brainstem');
        ProjectServer::actingAs($user)->tool(SearchRepositories::class, ['query' => 'brain'])
            ->assertOk()
            ->assertSee('Brainstem');
        ProjectServer::actingAs($user)->tool(GetRepository::class, [
            'repository_id' => $repository->getKey(),
        ])->assertOk()
            ->assertSee('git@github.com:ryanennns/brainstem.git');
        ProjectServer::actingAs($user)->tool(UpdateRepository::class, [
            'repository_id' => $repository->getKey(),
            'default_branch' => 'develop',
        ])->assertOk()
            ->assertSee('develop');

        $this->assertDatabaseHas('repositories', [
            'id' => $repository->getKey(),
            'user_id' => $user->getKey(),
            'default_branch' => 'develop',
        ]);
    }

    public function test_repositories_are_private_to_their_owner(): void
    {
        $repository = Repository::query()->create([
            'name' => 'Private',
            'user_id' => User::factory()->create()->getKey(),
        ]);
        $user = User::factory()->create();

        ProjectServer::actingAs($user)->tool(GetRepository::class, [
            'repository_id' => $repository->getKey(),
        ])->assertHasErrors(['Repository not found.']);
        ProjectServer::actingAs($user)->tool(UpdateRepository::class, [
            'repository_id' => $repository->getKey(),
            'name' => 'Stolen',
        ])->assertHasErrors(['Repository not found.']);

        $this->assertSame('Private', Repository::query()->findOrFail($repository->getKey())->name);
    }

    public function test_remote_urls_are_unique_per_user(): void
    {
        $user = User::factory()->create();
        Repository::query()->create([
            'name' => 'Brainstem',
            'remote_url' => 'git@github.com:ryanennns/brainstem.git',
            'user_id' => $user->getKey(),
        ]);
        Repository::query()->create([
            'name' => 'Another owner',
            'remote_url' => 'git@github.com:ryanennns/brainstem.git',
            'user_id' => User::factory()->create()->getKey(),
        ]);

        $this->assertDatabaseCount('repositories', 2);

        $this->expectException(QueryException::class);

        Repository::query()->create([
            'name' => 'Duplicate',
            'remote_url' => 'git@github.com:ryanennns/brainstem.git',
            'user_id' => $user->getKey(),
        ]);
    }
}
