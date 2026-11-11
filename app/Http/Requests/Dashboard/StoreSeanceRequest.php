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

class StoreSeanceRequest extends FormRequest
{
    use ValidatesManagedSeanceSchedule;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Seance::class) ?? false;
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
                    ->where('status', Salle::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'ticket_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->addSeanceScheduleConflictCheck($validator, null, Seance::STATUS_SCHEDULED);
    }

    /**
     * @return array<string, mixed>
     */
    public function seancePayload(): array
    {
        $film = Film::query()->findOrFail((int) $this->input('film_id'));

        return $this->seanceAttributesForPersistence($film, Seance::STATUS_SCHEDULED);
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
        ];
    }
}
