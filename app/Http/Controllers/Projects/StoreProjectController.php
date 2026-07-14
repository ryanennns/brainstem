<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreProjectController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $project = Project::query()->create([...$request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]), 'user_id' => $request->user()->id]);

        return response()->json($project, 201);
    }
}
