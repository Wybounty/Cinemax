<?php

namespace App\Actions\Dashboard;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteManagedUser
{
    public const OUTCOME_DELETED = 'deleted';

    public const OUTCOME_DEACTIVATED = 'deactivated';

    public function __invoke(User $actor, User $target): string
    {
        if ($target->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => __('Vous ne pouvez pas supprimer votre propre compte.'),
            ]);
        }

        if ($target->isSuperAdmin() && User::countActiveWithRoleSlug(Role::SLUG_SUPER_ADMIN) <= 1) {
            throw ValidationException::withMessages([
                'user' => __('Impossible de supprimer le dernier super administrateur.'),
            ]);
        }

        if ($target->hasPrivilegedRole() && ! $actor->canManageAdminUsers()) {
            throw ValidationException::withMessages([
                'user' => __('Seul un super administrateur peut supprimer ce compte.'),
            ]);
        }

        if ($target->isAdmin() && User::countActiveWithRoleSlug(Role::SLUG_ADMIN) <= 1) {
            throw ValidationException::withMessages([
                'user' => __('Impossible de supprimer le dernier administrateur.'),
            ]);
        }

        if ($target->hasReservations()) {
            $target->update(['is_active' => false]);

            return self::OUTCOME_DEACTIVATED;
        }

        DB::transaction(function () use ($target): void {
            $target->delete();
        });

        return self::OUTCOME_DELETED;
    }
}
