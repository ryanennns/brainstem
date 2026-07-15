<?php

namespace App\Mcp\Tools;

use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List repositories owned by the authenticated user.')]
class ListRepositories extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(Repository::query()
            ->where('user_id', $request->user()->getKey())
            ->paginate());
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
