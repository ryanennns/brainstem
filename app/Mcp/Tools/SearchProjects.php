<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Search the authenticated user\'s projects by name.')]
class SearchProjects extends Tool
{
    public function handle(Request $request): Response
    {
        $query = $request->validate(['query' => ['required', 'string', 'max:255']])['query'];

        $projects = Project::query()
            ->where('user_id', $request->user()->getKey())
            ->where('name', 'like', "%{$query}%")
            ->paginate();

        return Response::json($projects);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Text to match against project names.')
                ->required(),
        ];
    }
}
