<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reservation_id
 * @property string $ticket_code
 * @property string $status
 * @property Carbon $issued_at
 */
#[Fillable([
    'reservation_id',
    'ticket_code',
    'status',
    'issued_at',
])]
class Billet extends Model
{
    public const STATUS_VALID = 'valid';

    public const STATUS_USED = 'used';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return [
            self::STATUS_VALID,
            self::STATUS_USED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
