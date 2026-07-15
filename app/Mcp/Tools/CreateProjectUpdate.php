<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\ProjectUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add an update to a project owned by the authenticated user.')]
class CreateProjectUpdate extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid'],
            'type' => ['required', 'in:code_change,plan_updated,miscellaneous'],
            'summary' => ['required', 'string'],
        ]);

        $project = Project::query()
            ->whereKey($validated['project_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        if (! $project) {
            return Response::error('Project not found.');
        }

        $projectUpdate = ProjectUpdate::query()->create([
            ...$validated,
            'project_id' => $project->getKey(),
        ]);

        return Response::json($projectUpdate);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()
                ->description('The project UUID.')
                ->required(),
            'type' => $schema->string()
                ->enum(['code_change', 'plan_updated', 'miscellaneous'])
                ->description('The kind of project update.')
                ->required(),
            'summary' => $schema->string()
                ->description('A summary of the update.')
                ->required(),
        ];
    }
}
