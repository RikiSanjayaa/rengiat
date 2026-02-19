<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:120', 'alpha_dash:ascii', 'lowercase', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', new Enum(UserRole::class)],
            'subdit_id' => [
                Rule::requiredIf($this->input('role') === UserRole::Operator->value),
                'nullable',
                'integer',
                'exists:subdits,id',
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
