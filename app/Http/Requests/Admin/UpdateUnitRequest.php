<?php

namespace App\Http\Requests\Admin;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-units') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('units', 'name')->ignore($unit->id)],
            'order_index' => ['required', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
        ];
    }
}
