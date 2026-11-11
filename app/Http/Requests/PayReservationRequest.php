<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PayReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Reservation|null $reservation */
        $reservation = $this->route('reservation');

        return $reservation !== null
            && Gate::forUser($this->user())->allows('pay', $reservation);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'contact_name' => 'nom',
            'contact_email' => 'e-mail',
        ];
    }
}
