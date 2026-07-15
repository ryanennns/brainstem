<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a project owned by the authenticated user.')]
class CreateProject extends Tool
{
    public function handle(Request $request): Response
    {
        $project = Project::query()->create([
            ...$request->validate([
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ]),
            'user_id' => $request->user()->getKey(),
        ]);

        return Response::json($project);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The project name.')
                ->required(),
            'description' => $schema->string()
                ->description('An optional project description.'),
        ];
    }
}
