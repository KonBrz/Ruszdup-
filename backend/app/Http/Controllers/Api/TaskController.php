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
        return response()->json(Task::all());
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
            'trip_id' => 'required|integer|exists:trips,id',
            'title' => 'required|string|max:255',
            'completed' => 'sometimes|boolean',
        ]);
        $task = Task::create($validated);

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
        $task = Task::find($id);
        if (! $task) {
            return response()->json(['message' => 'Nie znaleziono zadania'], 404);
        }

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
        $task = Task::find($id);
        if (! $task) {
            return response()->json(['message' => 'Nie znaleziono zadania'], 404);
        }
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'completed' => 'sometimes|boolean',
        ]);

        $task->update($validated);

        return response()->json($task);
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
        $task = Task::find($id);
        if (! $task) {
            return response()->json(['message' => 'Nie znaleziono zadania'], 404);
        }
        $task->delete();

        return response()->json(null, 204);
    }
}
