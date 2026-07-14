<?php

namespace App\Http\Controllers\ProjectUpdates;

use App\Http\Controllers\Controller;
use App\Models\ProjectUpdate;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreProjectUpdateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectUpdate = ProjectUpdate::query()->create($request->validate([
            'project_id' => [
                'required',
                'uuid',
                Rule::exists('projects', 'id')
                    ->where(fn (Builder $query) => $query->where('user_id', $request->user()->getKey())),
            ],
            'type' => ['required', Rule::in(['code_change', 'plan_updated', 'miscellaneous'])],
            'summary' => ['required', 'string'],
        ]));

        return response()->json($projectUpdate, 201);
    }
}
