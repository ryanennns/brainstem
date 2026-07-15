<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreProjectController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'repository_id' => ['nullable', 'uuid'],
            'working_branches' => ['sometimes', 'array'],
            'working_branches.*' => ['string', 'distinct'],
        ]);

        if (isset($validated['repository_id']) && ! Repository::query()
            ->whereKey($validated['repository_id'])
            ->where('user_id', $request->user()->getKey())
            ->exists()) {
            throw ValidationException::withMessages(['repository_id' => 'Repository not found.']);
        }

        if (($validated['working_branches'] ?? []) !== [] && empty($validated['repository_id'])) {
            throw ValidationException::withMessages(['repository_id' => 'A repository is required for working branches.']);
        }

        $project = Project::query()->create([
            ...$validated,
            'user_id' => $request->user()->getKey(),
        ]);

        $project = Project::query()->with('repository')->findOrFail($project->getKey());

        return response()->json($project, 201);
    }
}
