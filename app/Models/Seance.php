<?php

namespace App\Models;

use App\Support\SeanceSeatAvailability;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $film_id
 * @property int $salle_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property string $ticket_price
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'film_id',
    'salle_id',
    'start_at',
    'end_at',
    'ticket_price',
    'status',
])]
class Seance extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const CLEANING_MINUTES = 15;

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return [
            self::STATUS_SCHEDULED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'ticket_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Film, $this>
     */
    public function film(): BelongsTo
    {
        return $this->belongsTo(Film::class);
    }

    /**
     * @return BelongsTo<Salle, $this>
     */
    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function seatAvailability(): SeanceSeatAvailability
    {
        return SeanceSeatAvailability::for($this);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Séances non annulées occupant encore la salle (projection + nettoyage).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOccupyingRoom(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * Séances affichables au public pour la réservation à venir.
     *
     * @param  Builder<self>  $query
     */
    public function scopeBookable(Builder $query): void
    {
        $query
            ->where('status', self::STATUS_SCHEDULED)
            ->where('start_at', '>=', now());
    }

    public function totalSeats(): int
    {
        return $this->salle?->capacity ?? 0;
    }

    public function confirmedSeatsCount(): int
    {
        return $this->seatAvailability()->confirmedSeats();
    }

    public function pendingSeatsCount(): int
    {
        return $this->seatAvailability()->pendingSeats();
    }

    public function reservedSeatsCount(): int
    {
        return $this->seatAvailability()->occupyingSeats();
    }

    public function remainingSeatsCount(): int
    {
        return $this->seatAvailability()->remainingSeats();
    }

    public function hasReservations(): bool
    {
        return $this->reservedSeatsCount() > 0;
    }
}
