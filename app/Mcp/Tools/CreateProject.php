<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a project owned by the authenticated user and optionally associate it with a repository.')]
class CreateProject extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'repository_id' => ['nullable', 'uuid'],
            'working_branches' => ['sometimes', 'array'],
            'working_branches.*' => ['string', 'distinct'],
        ]);

        if (isset($validated['repository_id']) && ! Repository::query()
            ->whereKey($validated['repository_id'])
            ->where('user_id', $request->user()->getKey())
            ->exists()) {
            return Response::error('Repository not found.');
        }

        if (($validated['working_branches'] ?? []) !== [] && empty($validated['repository_id'])) {
            return Response::error('A repository is required for working branches.');
        }

        $project = Project::query()->create([
            ...$validated,
            'user_id' => $request->user()->getKey(),
        ]);

        return Response::json(Project::query()->with('repository')->findOrFail($project->getKey()));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The project name.')
                ->required(),
            'description' => $schema->string()
                ->description('An optional project description.'),
            'repository_id' => $schema->string()
                ->nullable()
                ->description('The UUID of a repository owned by the authenticated user.'),
            'working_branches' => $schema->array()
                ->items($schema->string())
                ->description('Feature branches used specifically for this work. Leave empty when the project represents the whole repository.'),
        ];
    }
}
