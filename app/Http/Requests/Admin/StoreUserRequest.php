<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', 'alpha_dash:ascii', 'lowercase', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', new Enum(UserRole::class)],
            'unit_id' => [
                Rule::requiredIf($this->input('role') === UserRole::Operator->value),
                'nullable',
                'integer',
                'exists:units,id',
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
