<?php

namespace App\Actions\Dashboard;

use App\Models\Billet;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelManagedReservation
{
    public function __invoke(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation): Reservation {
            /** @var Reservation $locked */
            $locked = Reservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === Reservation::STATUS_CANCELLED) {
                return $locked;
            }

            if ($locked->status === Reservation::STATUS_EXPIRED) {
                throw ValidationException::withMessages([
                    'reservation' => __('Une réservation expirée ne peut pas être annulée depuis le tableau de bord.'),
                ]);
            }

            if (! in_array($locked->status, [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED], true)) {
                throw ValidationException::withMessages([
                    'reservation' => __('Cette réservation ne peut pas être annulée.'),
                ]);
            }

            $wasConfirmed = $locked->status === Reservation::STATUS_CONFIRMED;

            $locked->update([
                'status' => Reservation::STATUS_CANCELLED,
                'expires_at' => null,
            ]);

            if ($wasConfirmed) {
                $locked->billets()
                    ->where('status', Billet::STATUS_VALID)
                    ->update(['status' => Billet::STATUS_CANCELLED]);
            }

            return $locked->fresh();
        });
    }
}
