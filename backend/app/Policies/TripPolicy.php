<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Determine if user can view the trip (must be a member).
     */
    public function view(User $user, Trip $trip): bool
    {
        return $trip->tripUsers()->whereKey($user->id)->exists();
    }

    /**
     * Determine if user can update the trip (must be owner).
     */
    public function update(User $user, Trip $trip): bool
    {
        return (int) $trip->user_id === (int) $user->id;
    }

    /**
     * Determine if user can delete the trip (must be owner).
     */
    public function delete(User $user, Trip $trip): bool
    {
        return (int) $trip->user_id === (int) $user->id;
    }

    /**
     * Determine if user can generate invite link (must be owner).
     */
    public function invite(User $user, Trip $trip): bool
    {
        return (int) $trip->user_id === (int) $user->id;
    }

    /**
     * Determine if user can remove users from trip (must be owner).
     */
    public function removeUser(User $user, Trip $trip): bool
    {
        return (int) $trip->user_id === (int) $user->id;
    }
}
