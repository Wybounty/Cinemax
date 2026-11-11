<?php

namespace App\Support;

use App\Models\Film;
use App\Models\Seance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SeanceRoomSchedule
{
    public static function computeEndAt(Carbon $startAt, Film $film): Carbon
    {
        return $startAt->copy()->addMinutes($film->duration);
    }

    public static function hasConflict(
        int $salleId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $ignoreSeanceId = null,
    ): bool {
        $query = Seance::query()
            ->where('salle_id', $salleId)
            ->occupyingRoom()
            ->when(
                $ignoreSeanceId !== null,
                fn ($builder) => $builder->where('id', '!=', $ignoreSeanceId),
            );

        $blockedEnd = $endAt->copy()->addMinutes(Seance::CLEANING_MINUTES);

        /** @var Collection<int, Seance> $existing */
        $existing = $query->get(['id', 'start_at', 'end_at']);

        foreach ($existing as $seance) {
            $existingBlockedEnd = $seance->end_at->copy()->addMinutes(Seance::CLEANING_MINUTES);

            if ($startAt->lt($existingBlockedEnd) && $seance->start_at->lt($blockedEnd)) {
                return true;
            }
        }

        return false;
    }
}
