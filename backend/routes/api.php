<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\FlaggedController;
use App\Http\Controllers\Api\AiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    // CRUD dla trips
    Route::apiResource('trips', TripController::class);

    Route::delete('trips/{trip}/deleteuser/{user}', [TripController::class, 'deleteUser']);

    Route::post('/flagged', [FlaggedController::class, 'store']);

    Route::get('/tasks', [TaskController::class, 'allUserTasks']);

    Route::put('/tasks/update/{task}', [TaskController::class, 'updateCompletedAndIgnored'])
        ->middleware('auth:sanctum');

    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::post('/trips/{trip}/invite', [TripController::class, 'generateInviteLink'])
        ->middleware('auth');

    Route::post('/trip-invite/accept', [TripController::class, 'acceptInvitation'])
        ->middleware('auth:sanctum');

    // Nested tasks (np. GET /trips/1/tasks)
    Route::apiResource('trips.tasks', TaskController::class)->only(['index']);

    // rout dla ai advice
    Route::post('/ai/advice', [\App\Http\Controllers\Api\AiAdviceController::class, 'getAdvice']);

    Route::post('/ai/ask', [AiController::class, 'ask']);
});

// Endpoint chat AI (bez middleware auth:sanctum, sprawdzanie w kontrolerze dla lepszego komunikatu)
Route::post('/ai-chat', [AiController::class, 'chat'])
    ->middleware('throttle:ai');
