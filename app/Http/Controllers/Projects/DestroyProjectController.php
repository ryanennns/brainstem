<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Response;

class DestroyProjectController extends Controller
{
    public function __invoke(Project $project): Response
    {
        $project->delete();

        return response()->noContent();
    }
}
