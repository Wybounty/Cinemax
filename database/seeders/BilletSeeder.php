<?php

namespace Database\Seeders;

use App\Models\Billet;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class BilletSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = Reservation::query()
            ->where('reference', 'like', 'DEMO-R-%')
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->withCount('billets')
            ->get();

        foreach ($reservations as $reservation) {
            $missing = $reservation->quantity - $reservation->billets_count;

            if ($missing <= 0) {
                continue;
            }

            $startIndex = $reservation->billets_count + 1;

            for ($index = $startIndex; $index <= $reservation->quantity; $index++) {
                $ticketCode = sprintf(
                    'DEMO-T-%s-%02d',
                    str_replace('DEMO-R-', '', $reservation->reference),
                    $index,
                );

                Billet::query()->updateOrCreate(
                    ['ticket_code' => $ticketCode],
                    [
                        'reservation_id' => $reservation->id,
                        'status' => Billet::STATUS_VALID,
                        'issued_at' => $reservation->reserved_at->copy()->addMinutes(10),
                    ],
                );
            }
        }
    }
}
