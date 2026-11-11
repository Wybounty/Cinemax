<?php

namespace App\Http\Requests\Dashboard;

use App\Http\Requests\Dashboard\Concerns\ValidatesManagedSeanceSchedule;
use App\Models\Film;
use App\Models\Salle;
use App\Models\Seance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSeanceRequest extends FormRequest
{
    use ValidatesManagedSeanceSchedule;

    public function authorize(): bool
    {
        /** @var Seance|null $seance */
        $seance = $this->route('seance');

        return $seance !== null && ($this->user()?->can('update', $seance) ?? false);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'film_id' => [
                'required',
                'integer',
                Rule::exists('films', 'id')
                    ->where('published', true)
                    ->whereNull('deleted_at'),
            ],
            'salle_id' => [
                'required',
                'integer',
                Rule::exists('salles', 'id')
                    ->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'ticket_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', 'string', Rule::in(Seance::statusValues())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Seance $seance */
        $seance = $this->route('seance');

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $salle = Salle::query()->find((int) $this->input('salle_id'));

            if ($salle === null) {
                return;
            }

            $status = (string) $this->input('status');

            if ($status === Seance::STATUS_SCHEDULED && ! $salle->canHostNewScreenings()) {
                $validator->errors()->add(
                    'salle_id',
                    __('Cette salle n’est pas active et ne peut pas accueillir de nouvelles séances.'),
                );
            }
        });

        $this->addSeanceScheduleConflictCheck(
            $validator,
            $seance->id,
            (string) $this->input('status', $seance->status),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function seancePayload(): array
    {
        $film = Film::query()->findOrFail((int) $this->input('film_id'));

        return $this->seanceAttributesForPersistence($film, (string) $this->input('status'));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'film_id' => 'film',
            'salle_id' => 'salle',
            'date' => 'date',
            'start_time' => 'heure de début',
            'ticket_price' => 'prix du billet',
            'status' => 'statut',
        ];
    }
}
