<?php

namespace App\Actions\Dashboard;

use App\Models\Film;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArchiveManagedFilm
{
    public function __invoke(Film $film): void
    {
        if ($film->trashed()) {
            throw ValidationException::withMessages([
                'film' => __('Ce film est déjà archivé.'),
            ]);
        }

        if ($film->hasUpcomingScreenings()) {
            throw ValidationException::withMessages([
                'film' => __('Impossible d’archiver ce film : des séances sont planifiées.'),
            ]);
        }

        if ($film->hasActiveReservations()) {
            throw ValidationException::withMessages([
                'film' => __('Impossible d’archiver ce film : des réservations sont en cours.'),
            ]);
        }

        DB::transaction(function () use ($film): void {
            $film->update(['published' => false]);
            $film->delete();
        });
    }
}
