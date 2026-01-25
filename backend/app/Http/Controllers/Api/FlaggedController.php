<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Flagged;
use App\Models\Trip;
use App\Models\Task;

class FlaggedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id|required_without_all:trip_id,task_id',
            'trip_id' => 'nullable|integer|exists:trips,id|required_without_all:user_id,task_id',
            'task_id' => 'nullable|integer|exists:tasks,id|required_without_all:user_id,trip_id',
        ]);

        $userId = auth()->id();

        // Authorization: if flagging a trip, user must be a member
        if (!empty($validated['trip_id'])) {
            $trip = Trip::findOrFail($validated['trip_id']);
            if (!$trip->tripUsers()->whereKey($userId)->exists()) {
                abort(403);
            }
        }

        // Authorization: if flagging a task, user must be a member of its trip
        if (!empty($validated['task_id'])) {
            $task = Task::with('trip.tripUsers')->findOrFail($validated['task_id']);
            $trip = $task->trip;
            if (!$trip || !$trip->tripUsers()->whereKey($userId)->exists()) {
                abort(403);
            }
        }

        $flag = Flagged::create($validated);

        return response()->json($flag, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
