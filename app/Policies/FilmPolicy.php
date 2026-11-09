<?php

namespace App\Policies;

use App\Models\Film;
use App\Models\User;

class FilmPolicy
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

    public function update(User $user, Film $film): bool
    {
        return ! $film->trashed();
    }

    public function delete(User $user, Film $film): bool
    {
        return ! $film->trashed();
    }
}
