<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;

class ShowProjectController extends Controller
{
    public function __invoke(Project $project): Project
    {
        return Project::query()->with('repository')->findOrFail($project->getKey());
    }
}
