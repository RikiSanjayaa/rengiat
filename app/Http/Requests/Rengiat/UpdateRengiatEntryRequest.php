<?php

namespace App\Http\Requests\Rengiat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRengiatEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'description' => ['required', 'string'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'attachment' => [
                Rule::prohibitedIf(! config('rengiat.enable_attachments')),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ];
    }
}
