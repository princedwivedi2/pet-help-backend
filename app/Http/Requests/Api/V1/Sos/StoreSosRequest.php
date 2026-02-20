<?php

namespace App\Http\Requests\Api\V1\Sos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pet_id' => ['nullable', 'integer', 'exists:pets,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:300'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'emergency_type' => ['nullable', Rule::in(['injury', 'illness', 'poisoning', 'accident', 'breathing', 'seizure', 'other'])],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
            'description.min' => 'Please provide at least 10 characters describing the emergency.',
            'emergency_type.in' => 'Emergency type must be one of: injury, illness, poisoning, accident, breathing, seizure, other.',
        ];
    }
}
