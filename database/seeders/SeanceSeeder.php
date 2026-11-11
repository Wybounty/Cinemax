<?php

namespace Database\Seeders;

use App\Models\Film;
use App\Models\Salle;
use App\Models\Seance;
use App\Support\SeanceRoomSchedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SeanceSeeder extends Seeder
{
    private const TARGET_UPCOMING_PER_FILM = 2;

    /**
     * @var list<string>
     */
    private const DAILY_SLOT_TIMES = ['10:30', '14:00', '17:15', '20:30'];

    public function run(): void
    {
        /** @var Collection<int, Salle> $salles */
        $salles = Salle::query()
            ->where('status', Salle::STATUS_ACTIVE)
            ->notArchived()
            ->orderBy('id')
            ->get();

        if ($salles->isEmpty()) {
            throw new RuntimeException(
                'SeanceSeeder : au moins une salle active est requise pour générer des séances de démonstration.',
            );
        }

        $films = Film::query()
            ->forCatalog()
            ->orderBy('id')
            ->get();

        if ($films->isEmpty()) {
            return;
        }

        $minSlotsNeeded = $films->count() * self::TARGET_UPCOMING_PER_FILM;
        $slotsPerDay = count(self::DAILY_SLOT_TIMES) * $salles->count();

        if ($slotsPerDay < 1) {
            throw new RuntimeException('SeanceSeeder : configuration de créneaux invalide.');
        }

        $daysRequired = (int) ceil($minSlotsNeeded / $slotsPerDay);

        if ($daysRequired > 30) {
            throw new RuntimeException(
                'SeanceSeeder : pas assez de salles actives pour planifier toutes les séances sans conflit.',
            );
        }

        foreach ($films as $film) {
            $upcomingCount = $film->seances()
                ->where('status', Seance::STATUS_SCHEDULED)
                ->where('start_at', '>', now())
                ->count();

            $toCreate = self::TARGET_UPCOMING_PER_FILM - $upcomingCount;

            if ($toCreate <= 0) {
                continue;
            }

            for ($i = 0; $i < $toCreate; $i++) {
                $slot = $this->findNextAvailableSlot($salles, $film);

                if ($slot === null) {
                    Log::warning('SeanceSeeder : impossible de placer une séance supplémentaire.', [
                        'film_id' => $film->id,
                        'film_title' => $film->title,
                    ]);

                    break;
                }

                Seance::query()->create([
                    'film_id' => $film->id,
                    'salle_id' => $slot['salle_id'],
                    'start_at' => $slot['start_at'],
                    'end_at' => $slot['end_at'],
                    'ticket_price' => $this->ticketPriceFor($film),
                    'status' => Seance::STATUS_SCHEDULED,
                ]);
            }
        }
    }

    /**
     * @param  Collection<int, Salle>  $salles
     * @return array{salle_id: int, start_at: Carbon, end_at: Carbon}|null
     */
    private function findNextAvailableSlot(Collection $salles, Film $film): ?array
    {
        for ($dayOffset = 1; $dayOffset <= 30; $dayOffset++) {
            $date = now()->addDays($dayOffset)->startOfDay();

            foreach (self::DAILY_SLOT_TIMES as $time) {
                [$hour, $minute] = array_map('intval', explode(':', $time));

                foreach ($salles as $salle) {
                    $startAt = Carbon::parse(
                        $date->copy()->setTime($hour, $minute)->toDateTimeString(),
                    );

                    if ($startAt->lte(now())) {
                        continue;
                    }

                    $endAt = SeanceRoomSchedule::computeEndAt($startAt, $film);

                    if (SeanceRoomSchedule::hasConflict($salle->id, $startAt, $endAt, null)) {
                        continue;
                    }

                    return [
                        'salle_id' => $salle->id,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                    ];
                }
            }
        }

        return null;
    }

    private function ticketPriceFor(Film $film): string
    {
        $base = 8.5 + ($film->id % 5) * 1.25 + ($film->duration % 4) * 0.35;

        return number_format(round($base, 2), 2, '.', '');
    }
}
