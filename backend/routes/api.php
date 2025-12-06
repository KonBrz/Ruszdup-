<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\FlaggedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
// CRUD dla trips
    Route::apiResource('trips', TripController::class);

    Route::post('/flagged', [FlaggedController::class, 'store']);

    Route::get('/tasks', [TaskController::class, 'allUserTasks']);

    Route::post('/tasks', [TaskController::class, 'store']);

    Route::post('/trips/{trip}/invite', [TripController::class, 'generateInviteLink'])
        ->middleware('auth');

    Route::post('/trip-invite/accept', [TripController::class, 'acceptInvitation'])
        ->middleware('auth:sanctum');

// Nested tasks (np. GET /trips/1/tasks)
    Route::apiResource('trips.tasks', TaskController::class)->shallow();
});
