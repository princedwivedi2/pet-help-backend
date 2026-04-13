<?php

namespace App\Http\Requests\Api\V1\Pet;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdatePetMedicalRecordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Determine the effective record_type: either from the request or fallback to
        // the existing record's type so conditional validation stays consistent.
        $recordType = $this->input('record_type', $this->route('medical_record')?->record_type);

        return [
            'record_type' => ['sometimes', 'required', Rule::in(['diagnosis', 'vaccination', 'medicine', 'lab_report', 'general'])],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            'medicine_name' => [
                Rule::requiredIf(fn () => $recordType === 'medicine'),
                'nullable',
                'string',
                'max:255',
            ],
            'medicine_dosage' => ['nullable', 'string', 'max:100'],
            'medicine_frequency' => ['nullable', 'string', 'max:100'],
            'medicine_duration' => ['nullable', 'string', 'max:100'],

            'attachment_url' => ['nullable', 'url', 'max:500'],
            'recorded_at' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
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
