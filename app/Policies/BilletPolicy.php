<?php

namespace App\Policies;

use App\Models\Billet;
use App\Models\User;

class BilletPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Billet $billet): bool
    {
        return $billet->reservation?->user_id === $user->id;
    }
}
