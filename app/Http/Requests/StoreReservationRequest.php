<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() === null
            || $this->user()->can('create', Reservation::class);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        $guest = $this->user() === null;

        return [
            'seance_id' => ['required', 'integer', 'exists:seances,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'contact_name' => [$guest ? 'required' : 'sometimes', 'string', 'max:255'],
            'contact_email' => [$guest ? 'required' : 'sometimes', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'seance_id' => 'séance',
            'quantity' => 'nombre de places',
            'contact_name' => 'nom',
            'contact_email' => 'e-mail',
        ];
    }
}
