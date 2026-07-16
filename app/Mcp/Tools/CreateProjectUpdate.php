<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\ProjectAgentSession;
use App\Models\ProjectUpdate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add an update to a project owned by the authenticated user and attribute it to an agent session.')]
class CreateProjectUpdate extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid'],
            'agent' => ['required', 'string', 'max:255'],
            'agent_session_id' => ['required', 'string', 'max:255'],
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

        $agentSession = ProjectAgentSession::query()->firstOrCreate([
            'project_id' => $project->getKey(),
            'agent' => $validated['agent'],
            'session_id' => $validated['agent_session_id'],
        ]);

        $projectUpdate = ProjectUpdate::query()->create([
            'project_id' => $project->getKey(),
            'project_agent_session_id' => $agentSession->getKey(),
            'type' => $validated['type'],
            'summary' => $validated['summary'],
        ]);

        return Response::json(ProjectUpdate::query()
            ->with('agentSession')
            ->findOrFail($projectUpdate->getKey()));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()
                ->description('The project UUID.')
                ->required(),
            'agent' => $schema->string()
                ->description('The agent client, such as codex or claude.')
                ->required(),
            'agent_session_id' => $schema->string()
                ->description('The current agent session ID. Reuse it for every update from the same session.')
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
