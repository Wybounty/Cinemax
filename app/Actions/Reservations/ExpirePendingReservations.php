<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;

class ExpirePendingReservations
{
    public function __invoke(): int
    {
        return Reservation::query()
            ->where('status', Reservation::STATUS_PENDING)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => Reservation::STATUS_EXPIRED]);
    }
}
