<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Film;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFilmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Film::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'synopsis' => ['nullable', 'string', 'max:5000'],
            'poster' => ['nullable', 'image', 'max:5120'],
            'duration' => ['required', 'integer', 'min:1', 'max:600'],
            'minimum_age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'published' => ['sometimes', 'boolean'],
            'genre_ids' => ['required', 'array', 'min:1'],
            'genre_ids.*' => ['integer', Rule::exists('genres', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'titre',
            'synopsis' => 'synopsis',
            'poster' => 'affiche',
            'duration' => 'durée',
            'minimum_age' => 'âge minimum',
            'published' => 'publication',
            'genre_ids' => 'genres',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('published')) {
            $this->merge([
                'published' => filter_var(
                    $this->input('published'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE,
                ) ?? false,
            ]);
        }
    }
}
