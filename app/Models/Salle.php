<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $capacity
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'description', 'capacity', 'status'])]
class Salle extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeNotArchived(Builder $query): void
    {
        $query->whereNull('deleted_at');
    }

    /**
     * @return HasMany<Seance, $this>
     */
    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }

    public function hasUpcomingScreenings(): bool
    {
        return $this->seances()
            ->where('status', Seance::STATUS_SCHEDULED)
            ->where('end_at', '>', now())
            ->exists();
    }

    public function canHostNewScreenings(): bool
    {
        return $this->isActive() && ! $this->trashed();
    }
}
