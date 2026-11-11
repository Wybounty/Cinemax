<?php

namespace App\Actions\Reservations;

use App\Models\Billet;
use App\Models\Reservation;
use App\Models\User;
use App\Support\GuestReservationSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelReservation
{
    public function __invoke(?User $user, Reservation $reservation): Reservation
    {
        if ($reservation->user_id !== null) {
            if ($user === null || $reservation->user_id !== $user->id) {
                abort(403);
            }
        } elseif (! GuestReservationSession::canAccess($reservation)) {
            abort(403);
        }

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
                    'reservation' => __('Cette réservation a déjà expiré.'),
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
