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
            'order_index' => ['required', 'integer', 'min:1', 'unique:units,order_index'],
            'active' => ['required', 'boolean'],
        ];
    }
}
