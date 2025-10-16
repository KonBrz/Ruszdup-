<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Travel Planner API",
 *     version="1.0.0",
 *     description="Dokumentacja API do zarządzania podróżami"
 * )
 *
 * @OA\Tag(
 *     name="Trips",
 *     description="Operacje związane z podróżami"
 * )
 */
class TripController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/trips",
     *     tags={"Trips"},
     *     summary="Pobierz listę wszystkich podróży",
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista podróży została pobrana pomyślnie"
     *     )
     * )
     */
    public function index()
    {
        return response()->json(Trip::all());
    }

    /**
     * @OA\Post(
     *     path="/api/trips",
     *     tags={"Trips"},
     *     summary="Utwórz nową podróż",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title"},
     *
     *             @OA\Property(property="title", type="string", example="Wakacje w Toskanii"),
     *             @OA\Property(property="description", type="string", example="Odwiedzić Florencję i Sienę")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Podróż została utworzona pomyślnie"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Tymczasowo, aby powiązać wycieczkę z zalogowanym użytkownikiem
        // Jeśli nie masz jeszcze logowania, możesz to na razie pominąć
        $trip = $request->user()->trips()->create($validated);

        return response()->json($trip, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/trips/{id}",
     *     tags={"Trips"},
     *     summary="Pobierz szczegóły konkretnej podróży",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID podróży",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="Zwrócono dane podróży"),
     *     @OA\Response(response=404, description="Podróż nie znaleziona")
     * )
     */
    public function show($id)
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return response()->json(['message' => 'Nie znaleziono podróży'], 404);
        }

        return response()->json($trip);
    }

    /**
     * @OA\Put(
     *     path="/api/trips/{id}",
     *     tags={"Trips"},
     *     summary="Zaktualizuj dane podróży",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID podróży",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="string", example="Wakacje w Toskanii - aktualizacja"),
     *             @OA\Property(property="description", type="string", example="Florencja, Siena i Piza")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Podróż została zaktualizowana"),
     *     @OA\Response(response=404, description="Podróż nie znaleziona")
     * )
     */
    public function update(Request $request, $id)
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return response()->json(['message' => 'Nie znaleziono podróży'], 404);
        }
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        $trip->update($validated);

        return response()->json($trip);
    }

    /**
     * @OA\Delete(
     *     path="/api/trips/{id}",
     *     tags={"Trips"},
     *     summary="Usuń podróż",
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID podróży do usunięcia",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=204, description="Podróż została usunięta"),
     *     @OA\Response(response=404, description="Podróż nie znaleziona")
     * )
     */
    public function destroy($id)
    {
        $trip = Trip::find($id);
        if (! $trip) {
            return response()->json(['message' => 'Nie znaleziono podróży'], 404);
        }
        $trip->delete();

        return response()->json(null, 204);
    }
}
