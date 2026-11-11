<?php

namespace App\Support;

use App\Models\Seance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validation serveur lors de la future réservation.
 *
 * À utiliser dans une transaction avec verrouillage de la séance :
 * Seance::query()->whereKey($id)->lockForUpdate()->firstOrFail()
 */
class ReservationCapacityValidator
{
    public function assertCanReserve(Seance $seance, int $quantity): void
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => __('Sélectionnez au moins une place.'),
            ]);
        }

        DB::transaction(function () use ($seance, $quantity): void {
            $locked = Seance::query()
                ->whereKey($seance->id)
                ->lockForUpdate()
                ->firstOrFail();

            $remaining = SeanceSeatAvailability::for($locked)->remainingSeats();

            if ($quantity > $remaining) {
                throw ValidationException::withMessages([
                    'quantity' => __('Il ne reste que :count place(s) pour cette séance.', [
                        'count' => $remaining,
                    ]),
                ]);
            }
        });
    }
}
