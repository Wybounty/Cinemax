<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $guest_email
 * @property int $seance_id
 * @property string $reference
 * @property int $quantity
 * @property string $total_amount
 * @property string $status
 * @property Carbon $reserved_at
 * @property Carbon|null $expires_at
 */
#[Fillable([
    'user_id',
    'seance_id',
    'reference',
    'contact_name',
    'contact_email',
    'guest_email',
    'quantity',
    'total_amount',
    'status',
    'reserved_at',
    'expires_at',
    'tickets_email_sent_at',
])]
class Reservation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_EXPIRED = 'expired';

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total_amount' => 'decimal:2',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'tickets_email_sent_at' => 'datetime',
        ];
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Seance, $this>
     */
    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    /**
     * @return HasMany<Paiement, $this>
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * @return HasMany<Billet, $this>
     */
    public function billets(): HasMany
    {
        return $this->hasMany(Billet::class);
    }

    /**
     * Réservations qui occupent temporairement ou définitivement des places.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOccupyingSeats(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->where('status', self::STATUS_CONFIRMED)
                ->orWhere(function (Builder $pending): void {
                    $pending->where('status', self::STATUS_PENDING)
                        ->where(function (Builder $notExpired): void {
                            $notExpired->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                });
        });
    }

    public function isOccupyingSeats(): bool
    {
        if ($this->status === self::STATUS_CONFIRMED) {
            return true;
        }

        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
