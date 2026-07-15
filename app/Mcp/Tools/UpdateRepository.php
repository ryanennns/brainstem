<?php

namespace App\Mcp\Tools;

use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update a repository owned by the authenticated user.')]
class UpdateRepository extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'repository_id' => ['required', 'uuid'],
            'name' => ['sometimes', 'string', 'max:255'],
            'remote_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'default_branch' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $repository = Repository::query()
            ->whereKey($validated['repository_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        if (! $repository) {
            return Response::error('Repository not found.');
        }

        unset($validated['repository_id']);

        if ($validated === []) {
            return Response::error('Provide a name, remote URL, or default branch.');
        }

        $repository->update($validated);

        return Response::json(Repository::query()->findOrFail($repository->getKey()));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repository_id' => $schema->string()
                ->description('The repository UUID.')
                ->required(),
            'name' => $schema->string()
                ->description('The replacement repository name.'),
            'remote_url' => $schema->string()
                ->nullable()
                ->description('The replacement Git clone URL.'),
            'default_branch' => $schema->string()
                ->nullable()
                ->description('The replacement default branch.'),
        ];
    }
}
