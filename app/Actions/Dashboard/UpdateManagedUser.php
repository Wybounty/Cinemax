<?php

namespace App\Actions\Dashboard;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UpdateManagedUser
{
    /**
     * @param  array{name: string, email: string, role: string}  $validated
     */
    public function __invoke(User $actor, User $target, array $validated): void
    {
        $roleSlug = $validated['role'];
        $currentRoleSlug = $target->role?->slug;

        if ($roleSlug !== $currentRoleSlug && ! $actor->canManageAdminUsers()) {
            throw ValidationException::withMessages([
                'role' => __('Seul un super administrateur peut modifier les rôles des administrateurs.'),
            ]);
        }

        if ($target->isSuperAdmin()
            && $roleSlug !== Role::SLUG_SUPER_ADMIN
            && User::countActiveWithRoleSlug(Role::SLUG_SUPER_ADMIN) <= 1) {
            throw ValidationException::withMessages([
                'role' => __('Impossible de retirer le rôle au dernier super administrateur.'),
            ]);
        }

        if ($target->isAdmin()
            && $roleSlug !== Role::SLUG_ADMIN
            && User::countActiveWithRoleSlug(Role::SLUG_ADMIN) <= 1) {
            throw ValidationException::withMessages([
                'role' => __('Impossible de retirer le rôle admin au dernier administrateur.'),
            ]);
        }

        if ($target->id === $actor->id && $roleSlug !== $currentRoleSlug) {
            throw ValidationException::withMessages([
                'role' => __('Vous ne pouvez pas modifier votre propre rôle.'),
            ]);
        }

        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        $target->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $role->id,
        ]);
    }
}
