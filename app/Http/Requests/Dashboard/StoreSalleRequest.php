<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Salle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Salle::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('salles', 'name')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', 'string', Rule::in(Salle::statusValues())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'description' => 'description',
            'capacity' => 'capacité',
            'status' => 'statut',
        ];
    }
}
