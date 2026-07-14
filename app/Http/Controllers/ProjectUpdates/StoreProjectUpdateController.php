<?php

namespace App\Http\Controllers\ProjectUpdates;

use App\Http\Controllers\Controller;
use App\Models\ProjectUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreProjectUpdateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectUpdate = ProjectUpdate::query()->create($request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'type' => ['required', Rule::in(['code_change', 'plan_updated', 'miscellaneous'])],
            'summary' => ['required', 'string'],
        ]));

        return response()->json($projectUpdate, 201);
    }
}
