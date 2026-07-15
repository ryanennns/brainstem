<?php

namespace App\Mcp\Tools;

use App\Models\Repository;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Search the authenticated user\'s repositories by name.')]
class SearchRepositories extends Tool
{
    public function handle(Request $request): Response
    {
        $query = $request->validate(['query' => ['required', 'string', 'max:255']])['query'];

        return Response::json(Repository::query()
            ->where('user_id', $request->user()->getKey())
            ->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($query).'%'])
            ->paginate());
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Text to match against repository names.')
                ->required(),
        ];
    }
}
