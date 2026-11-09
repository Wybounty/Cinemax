<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->canAccessUserManagement() ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $model->role?->slug === Role::SLUG_SPECTATEUR;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && $model->role?->slug === Role::SLUG_SPECTATEUR;
    }
}
