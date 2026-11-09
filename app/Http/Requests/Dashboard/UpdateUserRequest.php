<?php

namespace App\Http\Requests\Dashboard;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('update', $target) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        /** @var User $actor */
        $actor = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($target->id),
            ],
            'role' => [
                'required',
                'string',
                Rule::in($actor->assignableRoleSlugsForUpdate($target)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'email' => 'adresse e-mail',
            'role' => 'rôle',
        ];
    }
}
