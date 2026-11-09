<?php

namespace App\Actions\Dashboard;

use App\Models\Salle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveManagedSalle
{
    public function __invoke(Salle $salle, bool $force = false): void
    {
        if ($salle->trashed()) {
            throw ValidationException::withMessages([
                'salle' => __('Cette salle est déjà archivée.'),
            ]);
        }

        if ($salle->hasUpcomingScreenings() && ! $force) {
            throw ValidationException::withMessages([
                'salle' => __('Cette salle possède des séances à venir. Confirmez l’archivage pour continuer.'),
                'requires_confirmation' => __('true'),
            ]);
        }

        DB::transaction(function () use ($salle): void {
            $salle->update(['status' => Salle::STATUS_INACTIVE]);
            $salle->delete();
        });
    }
}
