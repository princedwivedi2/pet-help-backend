<?php

namespace App\Http\Requests\Api\V1\Vet;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class VetUpdateProfileRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isVet() ?? false;
    }

    public function rules(): array
    {
        return [
            'clinic_name' => ['required', 'string', 'max:255'],
            'vet_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],

            'profile_photo' => ['nullable', 'string', 'max:500'],
            'license_number' => ['required', 'string', 'max:100'],
            'qualification' => ['required', 'string', 'max:1000'],
            'clinic_address' => ['required', 'string', 'max:500'],

            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],

            'specialization' => ['nullable', 'string', 'max:150'],
            'services' => ['required', 'array', 'min:1'],
            'accepted_species' => ['required', 'array', 'min:1'],
            'working_hours' => ['nullable', 'array'],
            'consultation_fee' => ['required', 'integer', 'min:0'],
            'home_visit_fee' => ['nullable', 'integer', 'min:0'],
            'online_fee' => ['nullable', 'integer', 'min:0'],
            'consultation_types' => ['nullable', 'array'],
            'consultation_types.*' => ['string', 'distinct', Rule::in(['clinic_visit', 'home_visit', 'online'])],
            'max_home_visit_km' => ['nullable', 'integer', 'min:0'],

            'degree_certificate' => ['nullable', 'string', 'max:500'],
            'government_id' => ['nullable', 'string', 'max:500'],
            'verification_documents' => ['nullable', 'array'],

            'is_emergency_available' => ['sometimes', 'boolean'],
            'is_24_hours' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_photo.required' => 'Profile photo is required.',
            'working_hours.required' => 'Working hours are required.',
            'license_number.required' => 'License number is required.',
            'qualification.required' => 'Qualification is required.',
            'clinic_address.required' => 'Clinic address is required.',
            'latitude.required' => 'Latitude is required.',
            'longitude.required' => 'Longitude is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('qualification') && $this->filled('qualifications')) {
            $this->merge(['qualification' => $this->input('qualifications')]);
        }

        if (!$this->filled('clinic_address') && $this->filled('address')) {
            $this->merge(['clinic_address' => $this->input('address')]);
        }
    }
}
