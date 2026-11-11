<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Seance;
use App\Models\User;
use App\Support\SeanceSeatAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservationSeeder extends Seeder
{
    /**
     * @var list<array{reference: string, user_email: string, seance_index: int, quantity: int, status: string, expires_in_minutes: int|null}>
     */
    private const DEMO_RESERVATIONS = [
        [
            'reference' => 'DEMO-R-00001',
            'user_email' => 'spectateur@example.com',
            'seance_index' => 0,
            'quantity' => 2,
            'status' => Reservation::STATUS_CONFIRMED,
            'expires_in_minutes' => null,
        ],
        [
            'reference' => 'DEMO-R-00002',
            'user_email' => 'spectateur@example.com',
            'seance_index' => 1,
            'quantity' => 3,
            'status' => Reservation::STATUS_CONFIRMED,
            'expires_in_minutes' => null,
        ],
        [
            'reference' => 'DEMO-R-00003',
            'user_email' => 'admin@example.com',
            'seance_index' => 2,
            'quantity' => 1,
            'status' => Reservation::STATUS_PENDING,
            'expires_in_minutes' => 30,
        ],
        [
            'reference' => 'DEMO-R-00004',
            'user_email' => 'spectateur@example.com',
            'seance_index' => 3,
            'quantity' => 4,
            'status' => Reservation::STATUS_PENDING,
            'expires_in_minutes' => 45,
        ],
        [
            'reference' => 'DEMO-R-00005',
            'user_email' => 'admin@example.com',
            'seance_index' => 4,
            'quantity' => 2,
            'status' => Reservation::STATUS_CANCELLED,
            'expires_in_minutes' => null,
        ],
        [
            'reference' => 'DEMO-R-00006',
            'user_email' => 'spectateur@example.com',
            'seance_index' => 5,
            'quantity' => 1,
            'status' => Reservation::STATUS_EXPIRED,
            'expires_in_minutes' => null,
        ],
        [
            'reference' => 'DEMO-R-00007',
            'user_email' => 'spectateur@example.com',
            'seance_index' => 6,
            'quantity' => 5,
            'status' => Reservation::STATUS_CONFIRMED,
            'expires_in_minutes' => null,
        ],
        [
            'reference' => 'DEMO-R-00008',
            'user_email' => 'admin@example.com',
            'seance_index' => 7,
            'quantity' => 2,
            'status' => Reservation::STATUS_CONFIRMED,
            'expires_in_minutes' => null,
        ],
    ];

    public function run(): void
    {
        $seances = Seance::query()
            ->bookable()
            ->with('salle')
            ->orderBy('start_at')
            ->get();

        if ($seances->isEmpty()) {
            return;
        }

        /** @var array<int, int> $plannedOccupancy */
        $plannedOccupancy = [];

        foreach ($seances as $seance) {
            $plannedOccupancy[$seance->id] = SeanceSeatAvailability::for($seance)->occupyingSeats();
        }

        foreach (self::DEMO_RESERVATIONS as $spec) {
            $seance = $seances->get($spec['seance_index']);

            if ($seance === null) {
                continue;
            }

            $user = User::query()->where('email', $spec['user_email'])->first();

            if ($user === null) {
                continue;
            }

            $quantity = $spec['quantity'];
            $isNew = ! Reservation::query()->where('reference', $spec['reference'])->exists();

            if ($isNew && $this->occupiesCapacity($spec['status'], $spec['expires_in_minutes'])) {
                $remaining = $seance->totalSeats() - ($plannedOccupancy[$seance->id] ?? 0);

                if ($quantity > $remaining) {
                    continue;
                }
            }

            $reservedAt = Carbon::now()->subHours(random_int(1, 48));
            $expiresAt = $this->resolveExpiresAt($spec['status'], $spec['expires_in_minutes'], $reservedAt);

            $totalAmount = round((float) $seance->ticket_price * $quantity, 2);

            Reservation::query()->updateOrCreate(
                ['reference' => $spec['reference']],
                [
                    'user_id' => $user->id,
                    'seance_id' => $seance->id,
                    'quantity' => $quantity,
                    'total_amount' => $totalAmount,
                    'status' => $spec['status'],
                    'reserved_at' => $reservedAt,
                    'expires_at' => $expiresAt,
                ],
            );

            if ($isNew && $this->occupiesCapacity($spec['status'], $spec['expires_in_minutes'])) {
                $plannedOccupancy[$seance->id] = ($plannedOccupancy[$seance->id] ?? 0) + $quantity;
            }
        }
    }

    private function occupiesCapacity(string $status, ?int $expiresInMinutes): bool
    {
        if ($status === Reservation::STATUS_CONFIRMED) {
            return true;
        }

        if ($status === Reservation::STATUS_PENDING) {
            return $expiresInMinutes === null || $expiresInMinutes > 0;
        }

        return false;
    }

    private function resolveExpiresAt(
        string $status,
        ?int $expiresInMinutes,
        Carbon $reservedAt,
    ): ?Carbon {
        if ($status === Reservation::STATUS_EXPIRED) {
            return $reservedAt->copy()->subMinutes(5);
        }

        if ($status === Reservation::STATUS_PENDING && $expiresInMinutes !== null) {
            return Carbon::parse(now()->addMinutes($expiresInMinutes));
        }

        return null;
    }
}
