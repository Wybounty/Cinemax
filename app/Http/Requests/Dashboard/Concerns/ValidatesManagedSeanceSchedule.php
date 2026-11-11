<?php

namespace App\Http\Requests\Dashboard\Concerns;

use App\Models\Film;
use App\Models\Seance;
use App\Support\SeanceRoomSchedule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator as ValidatorInstance;

trait ValidatesManagedSeanceSchedule
{
    protected function addSeanceScheduleConflictCheck(
        ValidatorInstance $validator,
        ?int $ignoreSeanceId,
        ?string $resultingStatus,
    ): void {
        $validator->after(function (ValidatorInstance $validator) use ($ignoreSeanceId, $resultingStatus): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($resultingStatus === Seance::STATUS_CANCELLED) {
                return;
            }

            $film = Film::query()->find((int) $this->input('film_id'));

            if ($film === null) {
                return;
            }

            $startAt = Carbon::parse(
                $this->string('date')->toString().' '.$this->string('start_time')->toString(),
            );
            $endAt = SeanceRoomSchedule::computeEndAt($startAt, $film);

            if (SeanceRoomSchedule::hasConflict(
                (int) $this->input('salle_id'),
                $startAt,
                $endAt,
                $ignoreSeanceId,
            )) {
                $validator->errors()->add(
                    'start_time',
                    __('La salle est déjà occupée sur ce créneau (délai de nettoyage de 15 minutes inclus).'),
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function seanceAttributesForPersistence(Film $film, string $status): array
    {
        $startAt = Carbon::parse(
            $this->string('date')->toString().' '.$this->string('start_time')->toString(),
        );

        return [
            'film_id' => (int) $this->input('film_id'),
            'salle_id' => (int) $this->input('salle_id'),
            'start_at' => $startAt,
            'end_at' => SeanceRoomSchedule::computeEndAt($startAt, $film),
            'ticket_price' => number_format((float) $this->input('ticket_price'), 2, '.', ''),
            'status' => $status,
        ];
    }
}
