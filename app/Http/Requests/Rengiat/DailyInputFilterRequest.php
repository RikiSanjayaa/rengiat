<?php

namespace App\Http\Requests\Rengiat;

use Illuminate\Foundation\Http\FormRequest;

class DailyInputFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'subdit_id' => ['nullable', 'integer', 'exists:subdits,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ];
    }
}
