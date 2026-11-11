<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\Seance;

class SeanceSeatAvailability
{
    public function __construct(
        private Seance $seance,
    ) {}

    public static function for(Seance $seance): self
    {
        return new self($seance);
    }

    public function totalCapacity(): int
    {
        return $this->seance->totalSeats();
    }

    public function confirmedSeats(): int
    {
        return (int) $this->seance->reservations()
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->sum('quantity');
    }

    public function pendingSeats(): int
    {
        return (int) $this->seance->reservations()
            ->where('status', Reservation::STATUS_PENDING)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('quantity');
    }

    public function occupyingSeats(): int
    {
        return (int) $this->seance->reservations()
            ->occupyingSeats()
            ->sum('quantity');
    }

    public function remainingSeats(): int
    {
        return max(0, $this->totalCapacity() - $this->occupyingSeats());
    }

    /**
     * @return array<string, int|bool>
     */
    public function toSummary(): array
    {
        $remaining = $this->remainingSeats();

        return [
            'salle_capacity' => $this->totalCapacity(),
            'confirmed_seats' => $this->confirmedSeats(),
            'pending_seats' => $this->pendingSeats(),
            'reserved_seats' => $this->occupyingSeats(),
            'remaining_seats' => $remaining,
            'is_sold_out' => $remaining === 0,
        ];
    }
}
