<?php

namespace App\Policies;

use App\Models\Salle;
use App\Models\User;

class SallePolicy
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

    public function update(User $user, Salle $salle): bool
    {
        return ! $salle->trashed();
    }

    public function delete(User $user, Salle $salle): bool
    {
        return ! $salle->trashed();
    }
}
