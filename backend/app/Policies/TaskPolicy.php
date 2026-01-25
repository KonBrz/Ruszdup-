<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine if user can view the task (must be member of trip).
     */
    public function view(User $user, Task $task): bool
    {
        $trip = $task->trip;
        if (!$trip) {
            return false;
        }

        return $trip->tripUsers()->whereKey($user->id)->exists();
    }

    /**
     * Determine if user can update the task (must be creator or trip owner).
     */
    public function update(User $user, Task $task): bool
    {
        $trip = $task->trip;
        if (!$trip) {
            return false;
        }

        return (int) $task->user_id === (int) $user->id
            || (int) $trip->user_id === (int) $user->id;
    }

    /**
     * Determine if user can delete the task (same as update).
     */
    public function delete(User $user, Task $task): bool
    {
        return $this->update($user, $task);
    }
}
