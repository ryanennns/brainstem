<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexProjectController extends Controller
{
    public function __invoke(): LengthAwarePaginator
    {
        return Project::query()->paginate();
    }
}
