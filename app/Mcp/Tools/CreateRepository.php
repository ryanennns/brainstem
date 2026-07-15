<?php

namespace App\Mcp\Tools;

use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a repository owned by the authenticated user, returning the existing repository when its remote URL is already registered.')]
class CreateRepository extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'remote_url' => ['nullable', 'string', 'max:2048'],
            'default_branch' => ['nullable', 'string', 'max:255'],
        ]);
        $userId = $request->user()->getKey();

        $repository = isset($validated['remote_url'])
            ? Repository::query()->firstOrCreate(
                ['user_id' => $userId, 'remote_url' => $validated['remote_url']],
                ['name' => $validated['name'], 'default_branch' => $validated['default_branch'] ?? null],
            )
            : Repository::query()->create([...$validated, 'user_id' => $userId]);

        return Response::json($repository);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The repository name.')
                ->required(),
            'remote_url' => $schema->string()
                ->description('An optional Git clone URL.'),
            'default_branch' => $schema->string()
                ->description('The repository default branch, such as main.'),
        ];
    }
}
