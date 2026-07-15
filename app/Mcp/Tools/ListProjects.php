<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List projects owned by the authenticated user.')]
class ListProjects extends Tool
{
    public function handle(Request $request): Response
    {
        $projects = Project::query()
            ->where('user_id', $request->user()->getKey())
            ->paginate();

        return Response::json($projects);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
