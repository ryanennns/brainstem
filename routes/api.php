<?php

use App\Http\Controllers\Projects\DestroyProjectController;
use App\Http\Controllers\Projects\IndexProjectController;
use App\Http\Controllers\Projects\ShowProjectController;
use App\Http\Controllers\Projects\StoreProjectController;
use App\Http\Controllers\ProjectUpdates\IndexProjectUpdateController;
use App\Http\Controllers\ProjectUpdates\ShowProjectUpdateController;
use App\Http\Controllers\ProjectUpdates\StoreProjectUpdateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('projects')->group(function () {
        Route::get('/', IndexProjectController::class);
        Route::post('/', StoreProjectController::class);
        Route::get('/{project}', ShowProjectController::class);
        Route::delete('/{project}', DestroyProjectController::class);
    });

    Route::prefix('project-updates')->group(function () {
        Route::get('/', IndexProjectUpdateController::class);
        Route::post('/', StoreProjectUpdateController::class);
        Route::get('/{projectUpdate}', ShowProjectUpdateController::class);
    });
});
