<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Tasks",
 *     description="Operacje związane z zadaniami podróży"
 * )
 */
class TaskController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     summary="Pobierz listę wszystkich zadań",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista zadań została pobrana pomyślnie"
     *     )
     * )
     */
    public function index(Trip $trip)
    {
        $user = auth()->user();
        $isMember = $trip->tripUsers()->where('user_id', $user->id)->exists();
        if (!$isMember) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(
            $trip->tasks()->with('trip', 'taskUsers')->get());
    }

    public function allUserTasks()
    {
        $user = auth()->user();
        return response()->json(Task::whereHas('taskUsers', fn($q) => $q->where('user_id', $user->id))
            ->with('taskUsers')
            ->get());
    }

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     summary="Utwórz nowe zadanie",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"trip_id", "title"},
     *
     *             @OA\Property(property="trip_id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Spakować walizkę"),
     *             @OA\Property(property="is_completed", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Zadanie zostało utworzone")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'nullable|in:niski,średni,wysoki',
            'deadline' => 'nullable|date',
            'trip_id' => 'required|exists:trips,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Check if user is member of the trip
        $trip = Trip::findOrFail($validated['trip_id']);
        $userId = auth()->id();
        if (!$trip->tripUsers()->whereKey($userId)->exists()) {
            abort(403);
        }

        // Tworzymy task
        $task = $request->user()->tasks()->create($validated);


        if ($request->has('user_ids')) {
            $task->taskUsers()->sync($request->user_ids);
        }

        return response()->json($task, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Pobierz konkretne zadanie",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID zadania",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="Zwrócono dane zadania"),
     *     @OA\Response(response=404, description="Zadanie nie znalezione")
     * )
     */
    public function show($id)
    {
        $task = Task::with('trip.tripUsers:id,name', 'taskUsers:id,name')->findOrFail($id);

        $this->authorize('view', $task);

        $userId = auth()->id();

        $task->can_edit = (
            $task->user_id === $userId
        );

        return response()->json($task);
    }

    /**
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Zaktualizuj dane zadania",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID zadania do zaktualizowania",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="string", example="Kupić bilety lotnicze"),
     *             @OA\Property(property="is_completed", type="boolean", example=true)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Zadanie zostało zaktualizowane"),
     *     @OA\Response(response=404, description="Zadanie nie znalezione")
     * )
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'priority' => 'nullable|in:niski,średni,wysoki',
            'deadline' => 'nullable|date',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $task->update($validated);

        $request->validate([
            'completed' => 'sometimes|boolean',
            'ignored'   => 'sometimes|boolean',
        ]);

        $currentUserId = auth()->id();

        $completed = (bool) $request->completed;
        $ignored = (bool) $request->ignored;

        $task->taskUsers()->updateExistingPivot($currentUserId, [
            'completed' => $completed,
            'ignored' => $ignored,
        ]);

        if ($request->has('user_ids')) {
            $task->taskUsers()->sync($request->user_ids); // teraz zwykłe ID
            }

        return response()->json($task->load('taskUsers'));
    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Usuń zadanie",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID zadania do usunięcia",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=204, description="Zadanie zostało usunięte"),
     *     @OA\Response(response=404, description="Zadanie nie znalezione")
     * )
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        $this->authorize('delete', $task);

        $task->delete();

        return response()->json('Zadanie usunięte', 200);
    }

    public function updateCompletedAndIgnored(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $request->validate([
            'completed' => 'required|boolean',
            'ignored' => 'required|boolean',
        ]);

        $currentUserId = auth()->id();

        // Check if user is assigned to the task
        $isAssigned = $task->taskUsers()->whereKey($currentUserId)->exists();
        if (!$isAssigned) {
            abort(403);
        }

        $completed = (bool) $request->completed;
        $ignored = (bool) $request->ignored;

        $task->taskUsers()->updateExistingPivot($currentUserId, [
            'completed' => $completed,
            'ignored' => $ignored,
        ]);

        return response()->json($task->load('taskUsers'));
    }
}
