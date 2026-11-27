<?php

namespace App\Mcp\Tools;

use App\Models\Memory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Save the goal and outcome of a completed agent session. Saving the same source and session ID again replaces the previous memory.')]
class CreateMemory extends Tool
{
    public function handle(Request $request): Response
    {
        $memory = $request->validate(Memory::sessionMetadataRules() + [
            'summary' => ['required', 'string'],
        ]);

        Memory::saveSessionSummary($request->user()->getKey(), $memory['device'], $memory['source'], $memory['session_id'], $memory['summary']);

        return Response::text('Session memory saved.');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'device' => $schema->string()->max(255)
                ->description('The name of the device where the session ran.')
                ->required(),
            'source' => $schema->string()->enum(Memory::SOURCES)
                ->description('The agent client that ran the session.')
                ->required(),
            'session_id' => $schema->string()->max(255)
                ->description('The native session ID assigned by the agent client.')
                ->required(),
            'summary' => $schema->string()
                ->description('A concise account of the user’s goal and the actual outcome, including unfinished work or blockers.')
                ->required(),
        ];
    }
}
