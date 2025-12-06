<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

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
    public function index()
    {
        $user = auth()->user();

        return response()->json(
            $user->tasks()->with('trip', 'taskUsers')->get());
    }

    public function allUserTasks()
    {
        $user = auth()->user();
        return response()->json($user->taskUsers()->with('trips', 'taskUsers')->get());
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

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'priority' => 'nullable|in:niski,średni,wysoki',
            'deadline' => 'nullable|date',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id', // tylko ID, nie obiekty

            // pivot dla aktualnego użytkownika
            'completed' => 'sometimes|boolean',
            'ignored' => 'sometimes|boolean',
        ]);

        $task->update($validated);

        /** SYNC użytkowników z taska */
        if ($request->has('user_ids')) {
            $task->taskUsers()->sync($request->user_ids); // teraz zwykłe ID

            /** Update pivotu TYLKO dla zalogowanego użytkownika */
            $currentUserId = auth()->id();

            if (in_array($currentUserId, $request->user_ids)) {
                $task->taskUsers()->updateExistingPivot($currentUserId, [
                    'completed' => $request->completed ?? false,
                    'ignored' => $request->ignored ?? false,
                ]);
            }
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

        $task->delete();

        return response()->json('Zadanie usunięte', 200);
    }
}
