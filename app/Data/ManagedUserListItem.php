<?php

namespace App\Data;

use App\Models\User;

class ManagedUserListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ? [
                'slug' => $user->role->slug,
                'name' => $user->role->name,
            ] : null,
            'created_at' => $user->created_at?->toIso8601String(),
            'created_at_formatted' => $user->created_at?->format('d/m/Y H:i'),
        ];
    }
}
