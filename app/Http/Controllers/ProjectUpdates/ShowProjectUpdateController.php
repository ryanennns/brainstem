<?php

namespace App\Http\Controllers\ProjectUpdates;

use App\Http\Controllers\Controller;
use App\Models\ProjectUpdate;

class ShowProjectUpdateController extends Controller
{
    public function __invoke(ProjectUpdate $projectUpdate): ProjectUpdate
    {
        return $projectUpdate;
    }
}
