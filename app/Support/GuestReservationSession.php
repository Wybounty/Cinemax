<?php

namespace App\Support;

use App\Models\Reservation;

class GuestReservationSession
{
    private const SESSION_KEY = 'guest_reservation_ids';

    public static function remember(Reservation $reservation): void
    {
        if ($reservation->user_id !== null) {
            return;
        }

        $ids = session(self::SESSION_KEY, []);

        if (! in_array($reservation->id, $ids, true)) {
            $ids[] = $reservation->id;
        }

        session([self::SESSION_KEY => $ids]);
    }

    public static function canAccess(Reservation $reservation): bool
    {
        if ($reservation->user_id !== null) {
            return false;
        }

        return in_array($reservation->id, session(self::SESSION_KEY, []), true);
    }

    public static function forget(Reservation $reservation): void
    {
        $ids = array_values(array_filter(
            session(self::SESSION_KEY, []),
            fn (int $id): bool => $id !== $reservation->id,
        ));

        session([self::SESSION_KEY => $ids]);
    }
}
