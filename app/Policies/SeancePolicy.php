<?php

namespace App\Policies;

use App\Models\Seance;
use App\Models\User;

class SeancePolicy
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

    public function update(User $user, Seance $seance): bool
    {
        return true;
    }

    public function delete(User $user, Seance $seance): bool
    {
        return true;
    }
}
