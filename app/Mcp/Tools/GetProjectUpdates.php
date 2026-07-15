<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\ProjectUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List updates for a project owned by the authenticated user.')]
class GetProjectUpdates extends Tool
{
    public function handle(Request $request): Response
    {
        $project = Project::query()
            ->whereKey($request->validate(['project_id' => ['required', 'uuid']])['project_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        if (! $project) {
            return Response::error('Project not found.');
        }

        return Response::json(ProjectUpdate::query()
            ->where('project_id', $project->getKey())
            ->paginate());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()
                ->description('The project UUID.')
                ->required(),
        ];
    }
}
