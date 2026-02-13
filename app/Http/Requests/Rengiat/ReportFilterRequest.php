<?php

namespace App\Http\Requests\Rengiat;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
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
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'subdit_id' => ['nullable', 'integer', 'exists:subdits,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'keyword' => ['nullable', 'string', 'max:255'],
        ];
    }
}
