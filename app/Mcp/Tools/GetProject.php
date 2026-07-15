<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get a project owned by the authenticated user.')]
class GetProject extends Tool
{
    public function handle(Request $request): Response
    {
        $project = Project::query()
            ->whereKey($request->validate(['project_id' => ['required', 'uuid']])['project_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        return $project ? Response::json($project) : Response::error('Project not found.');
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
