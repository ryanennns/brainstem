<?php

namespace App\Http\Controllers\ProjectUpdates;

use App\Http\Controllers\Controller;
use App\Models\ProjectUpdate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexProjectUpdateController extends Controller
{
    public function __invoke(): LengthAwarePaginator
    {
        return ProjectUpdate::query()->paginate();
    }
}
