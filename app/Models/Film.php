<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $synopsis
 * @property string|null $poster
 * @property int $duration
 * @property int|null $minimum_age
 * @property bool $published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['title', 'synopsis', 'poster', 'duration', 'minimum_age', 'published'])]
class Film extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'film_genre');
    }

    /**
     * Films visibles dans le catalogue public (publiés, non archivés).
     *
     * @param  Builder<self>  $query
     */
    public function scopeForCatalog(Builder $query): void
    {
        $query->where('published', true);
    }

    public function hasUpcomingScreenings(): bool
    {
        if (! class_exists('App\Models\Screening')) {
            return false;
        }

        return $this->hasMany('App\Models\Screening')->exists();
    }

    public function hasActiveReservations(): bool
    {
        if (! class_exists('App\Models\Screening')) {
            return false;
        }

        if (! method_exists('App\Models\Screening', 'reservations')) {
            return false;
        }

        return $this->hasMany('App\Models\Screening')
            ->whereHas('reservations')
            ->exists();
    }
}
