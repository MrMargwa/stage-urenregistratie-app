<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

/**
 * Regelt de toegang tot tijdregistraties.
 *
 * Elke gebruiker — ook de admin — ziet en beheert alleen zijn eigen uren.
 * De lijstweergave wordt bovendien op queryniveau beperkt door de `ownedBy`
 * scope in TimeEntryResource::getEloquentQuery(), zodat een individuele
 * registratie van een ander nooit zichtbaar of bes-editbaar is.
 */
class TimeEntryPolicy
{
    /**
     * Iedere ingelogde gebruiker mag de lijst zien; de daadwerkelijke
     * beperking tot eigen uren gebeurt via de `ownedBy` query-scope.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        return $timeEntry->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        return $timeEntry->user_id === $user->id;
    }

    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        return $timeEntry->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
