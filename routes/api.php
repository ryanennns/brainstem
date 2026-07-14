<?php

use App\Http\Controllers\Projects\DestroyProjectController;
use App\Http\Controllers\Projects\IndexProjectController;
use App\Http\Controllers\Projects\ShowProjectController;
use App\Http\Controllers\Projects\StoreProjectController;
use App\Http\Controllers\ProjectUpdates\IndexProjectUpdateController;
use App\Http\Controllers\ProjectUpdates\ShowProjectUpdateController;
use App\Http\Controllers\ProjectUpdates\StoreProjectUpdateController;
use Illuminate\Support\Facades\Route;

Route::get('projects', IndexProjectController::class);
Route::post('projects', StoreProjectController::class);
Route::get('projects/{project}', ShowProjectController::class);
Route::delete('projects/{project}', DestroyProjectController::class);

Route::get('project-updates', IndexProjectUpdateController::class);
Route::post('project-updates', StoreProjectUpdateController::class);
Route::get('project-updates/{projectUpdate}', ShowProjectUpdateController::class);
