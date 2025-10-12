<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Standardowe trasy CRUD dla wycieczek
    Route::apiResource('trips', TripController::class);

    // Trasa niestandardowa do zapraszania użytkowników
    Route::post('trips/{trip}/invite', [TripController::class, 'inviteUser'])->name('trips.invite');

    // Trasy dla zadań w ramach wycieczki
    Route::apiResource('trips.tasks', TaskController::class)->shallow();
});
