<?php

namespace App\Data;

use App\Models\Salle;

class ManagedSalleListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Salle $salle): array
    {
        return [
            'id' => $salle->id,
            'name' => $salle->name,
            'description' => $salle->description,
            'description_excerpt' => $salle->description
                ? (strlen($salle->description) > 80
                    ? substr($salle->description, 0, 80).'…'
                    : $salle->description)
                : '—',
            'capacity' => $salle->capacity,
            'status' => $salle->status,
            'is_archived' => $salle->trashed(),
        ];
    }
}
