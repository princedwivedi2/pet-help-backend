<?php

namespace App\Http\Requests\Api\V1\Pet;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StorePetMedicalRecordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'record_type' => ['required', Rule::in(['diagnosis', 'vaccination', 'medicine', 'lab_report', 'general'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            // Medicine-specific fields — required when record_type is 'medicine'
            'medicine_name' => [
                Rule::requiredIf(fn () => $this->input('record_type') === 'medicine'),
                'nullable',
                'string',
                'max:255',
            ],
            'medicine_dosage' => ['nullable', 'string', 'max:100'],
            'medicine_frequency' => ['nullable', 'string', 'max:100'],
            'medicine_duration' => ['nullable', 'string', 'max:100'],

            'attachment_url' => ['nullable', 'url', 'max:500'],
            'recorded_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'record_type.in' => 'Record type must be one of: diagnosis, vaccination, medicine, lab_report, general.',
            'medicine_name.required_if' => 'Medicine name is required when record type is medicine.',
            'recorded_at.before_or_equal' => 'Recorded date cannot be in the future.',
        ];
    }
}
