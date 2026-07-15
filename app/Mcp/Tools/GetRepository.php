<?php

namespace App\Mcp\Tools;

use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get a repository owned by the authenticated user.')]
class GetRepository extends Tool
{
    public function handle(Request $request): Response
    {
        $repository = Repository::query()
            ->whereKey($request->validate(['repository_id' => ['required', 'uuid']])['repository_id'])
            ->where('user_id', $request->user()->getKey())
            ->first();

        return $repository ? Response::json($repository) : Response::error('Repository not found.');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'repository_id' => $schema->string()
                ->description('The repository UUID.')
                ->required(),
        ];
    }
}
