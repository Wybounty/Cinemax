<?php

namespace App\Actions\Reservations;

use App\Models\Billet;
use App\Models\Reservation;
use Illuminate\Support\Str;

class IssueReservationBillets
{
    public function __invoke(Reservation $reservation): void
    {
        $existingCount = $reservation->billets()->count();
        $missing = $reservation->quantity - $existingCount;

        if ($missing <= 0) {
            return;
        }

        $issuedAt = now();

        for ($i = 0; $i < $missing; $i++) {
            Billet::query()->create([
                'reservation_id' => $reservation->id,
                'ticket_code' => $this->generateTicketCode(),
                'status' => Billet::STATUS_VALID,
                'issued_at' => $issuedAt,
            ]);
        }
    }

    private function generateTicketCode(): string
    {
        do {
            $code = 'BLT-'.strtoupper(Str::random(16));
        } while (Billet::query()->where('ticket_code', $code)->exists());

        return $code;
    }
}
