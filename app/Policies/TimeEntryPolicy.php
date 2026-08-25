<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isAdmin() || $timeEntry->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isAdmin() || $timeEntry->user_id === $user->id;
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isAdmin() || $timeEntry->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }

    public function restore(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isAdmin() || $timeEntry->user_id === $user->id;
    }

    public function forceDelete(User $user, TimeEntry $timeEntry): bool
    {
        return $user->isAdmin() || $timeEntry->user_id === $user->id;
    }
}
