<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update a project name or description owned by the authenticated user.')]
class UpdateProject extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $project = Project::query()
            ->whereKey($validated['project_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        if (! $project) {
            return Response::error('Project not found.');
        }

        unset($validated['project_id']);

        if ($validated === []) {
            return Response::error('Provide a name or description.');
        }

        $project->update($validated);

        return Response::json($project->refresh());
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
        ];
    }
}
