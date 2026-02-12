<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-units') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', 'unique:units,name'],
            'order_index' => ['required', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
        ];
    }
}
