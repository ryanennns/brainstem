<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update a project name, description, repository, or working branches owned by the authenticated user.')]
class UpdateProject extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'repository_id' => ['sometimes', 'nullable', 'uuid'],
            'working_branches' => ['sometimes', 'array'],
            'working_branches.*' => ['string', 'distinct'],
        ]);

        $project = Project::query()
            ->whereKey($validated['project_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        if (! $project) {
            return Response::error('Project not found.');
        }

        if (isset($validated['repository_id']) && ! Repository::query()
            ->whereKey($validated['repository_id'])
            ->where('user_id', $request->user()->getKey())
            ->exists()) {
            return Response::error('Repository not found.');
        }

        unset($validated['project_id']);

        if ($validated === []) {
            return Response::error('Provide a name, description, repository, or working branches.');
        }

        $repositoryId = array_key_exists('repository_id', $validated)
            ? $validated['repository_id']
            : $project->repository_id;
        $workingBranches = $validated['working_branches'] ?? $project->working_branches ?? [];

        if ($workingBranches !== [] && empty($repositoryId)) {
            return Response::error('A repository is required for working branches.');
        }

        $project->update($validated);

        return Response::json(Project::query()->with('repository')->findOrFail($project->getKey()));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()
                ->description('The project UUID.')
                ->required(),
            'name' => $schema->string()
                ->description('The replacement project name.'),
            'description' => $schema->string()
                ->nullable()
                ->description('The replacement project description.'),
            'repository_id' => $schema->string()
                ->nullable()
                ->description('The replacement repository UUID, or null to detach the repository.'),
            'working_branches' => $schema->array()
                ->items($schema->string())
                ->description('The complete list of feature branches used specifically for this work.'),
        ];
    }
}
