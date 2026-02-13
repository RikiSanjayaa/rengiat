<?php

namespace App\Http\Requests\Rengiat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRengiatEntryRequest extends FormRequest
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
            'subdit_id' => ['required', 'integer', 'exists:subdits,id'],
            'entry_date' => ['required', 'date'],
            'time_start' => ['nullable', 'date_format:H:i'],
            'description' => ['required', 'string'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
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
