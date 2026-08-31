<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->isAdmin() || $user->id === $model->id) {
            return false;
        }

        return ! $this->isLastAdmin($model);
    }

    public function deleteAny(User $user): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        $adminIds = User::where('role', Role::Admin)->pluck('id');

        return $adminIds->reject(fn ($id) => $id === $user->id)->isNotEmpty();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    private function isLastAdmin(User $model): bool
    {
        return $model->isAdmin()
            && User::where('role', Role::Admin)->where('id', '!=', $model->id)->doesntExist();
    }
}
