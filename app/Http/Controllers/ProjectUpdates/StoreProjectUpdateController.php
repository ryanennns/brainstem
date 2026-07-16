<?php

namespace App\Http\Controllers\ProjectUpdates;

use App\Http\Controllers\Controller;
use App\Models\ProjectAgentSession;
use App\Models\ProjectUpdate;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreProjectUpdateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => [
                'required',
                'uuid',
                Rule::exists('projects', 'id')
                    ->where(fn (Builder $query) => $query->where('user_id', $request->user()->getKey())),
            ],
            'agent' => ['required', 'string', 'max:255'],
            'agent_session_id' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['code_change', 'plan_updated', 'miscellaneous'])],
            'summary' => ['required', 'string'],
        ]);

        $agentSession = ProjectAgentSession::query()->firstOrCreate([
            'project_id' => $validated['project_id'],
            'agent' => $validated['agent'],
            'session_id' => $validated['agent_session_id'],
        ]);

        $projectUpdate = ProjectUpdate::query()->create([
            'project_id' => $validated['project_id'],
            'project_agent_session_id' => $agentSession->getKey(),
            'type' => $validated['type'],
            'summary' => $validated['summary'],
        ]);

        return response()->json($projectUpdate->load('agentSession'), 201);
    }
}
