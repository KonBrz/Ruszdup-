<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\TripInvitation;
use App\Models\Trip;

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
        $user = auth()->user();

        return response()->json(
           $user->tripUsers()->get());
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
            'destination' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);


        // Tworzymy trip powiązany z zalogowanym userem
        $trip = $request->user()->trips()->create($validated);
        $trip->tripUsers()->attach(auth()->id());

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
        $trip = Trip::with('user:id,name', 'tripUsers', 'tasks', 'tasks.taskUsers:id,name')->findOrFail($id);

        $userId = auth()->id();

        $trip->can_edit_trip = (
            $trip->user_id === $userId
        );
        foreach ($trip->tasks as $task) {
            $task->can_edit_task =
                $trip->can_edit_trip ||
                $task->user_id === $userId;
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
        $trip = Trip::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'destination' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
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
        $trip = Trip::findOrFail($id);

        $trip->delete();

        return response()->json("Wycieczka usunięta", 200);
    }

    public function generateInviteLink($tripId)
    {
        $trip = Trip::findOrFail($tripId);

        $token = Str::uuid();

        // Zapis w tabeli trip_invitations
        $trip->invitations()->create(['token' => $token]);

        $link = config('app.frontend_url') . "/trips/$trip->id?invite_token=$token";

        return response()->json(['link' => $link]);
    }

    public function acceptInvitation(Request $request)
    {
        $token = $request->token;

        $invitation = TripInvitation::where('token', $token)->firstOrFail();
        $trip = $invitation->trip;

        $trip->tripUsers()->syncWithoutDetaching(auth()->id());

        return response()->json(['success' => true]);
    }
}
