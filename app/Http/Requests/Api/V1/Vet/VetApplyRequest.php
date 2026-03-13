<?php

namespace App\Http\Requests\Api\V1\Vet;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Validator;

class VetApplyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true; // Public endpoint — anyone can apply
    }

    public function rules(): array
    {
        return [
            // Personal info
            'full_name'            => ['required', 'string', 'max:150'],
            'email'                => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'             => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'confirmed'],
            'phone_number'         => ['required_without:phone', 'nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/'],
            'phone'                => ['required_without:phone_number', 'nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/'],
            'profile_photo'        => ['required', 'string', 'max:500'],

            // Clinic info
            'clinic_name'          => ['required', 'string', 'max:200'],
            'clinic_address'       => ['required', 'string', 'max:300'],
            'city'                 => ['required', 'string', 'max:100'],
            'state'                => ['nullable', 'string', 'max:100'],
            'postal_code'          => ['nullable', 'string', 'max:20'],
            'latitude'             => ['required', 'numeric', 'between:-90,90'],
            'longitude'            => ['required', 'numeric', 'between:-180,180'],
            'device_latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'device_longitude'     => ['nullable', 'numeric', 'between:-180,180'],
            'location_override_confirmed' => ['nullable', 'boolean'],

            // Professional info
            'qualifications'       => ['required', 'string', 'max:2000'],
            'qualification'        => ['required', 'string', 'max:2000'],
            'license_number'       => ['required', 'string', 'max:100', 'unique:vet_profiles,license_number'],
            'years_of_experience'  => ['required', 'integer', 'min:0', 'max:60'],
            'specialization'       => ['required', 'string', 'max:150'],
            'consultation_fee'     => ['required', 'integer', 'min:0'],
            'home_visit_fee'       => ['nullable', 'integer', 'min:0'],
            'accepted_species'     => ['required', 'array', 'min:1'],
            'accepted_species.*'   => ['string', 'max:50'],
            'services_offered'     => ['required', 'array', 'min:1'],
            'services_offered.*'   => ['string', 'max:100'],
            'working_hours'        => ['nullable', 'array'],
            'government_id'        => ['nullable', 'string', 'max:500'],
            'degree_certificate'   => ['nullable', 'string', 'max:500'],
            'verification_documents' => ['nullable', 'array'],
            'verification_documents.*' => ['string', 'max:500'],

            // Optional document uploads (PDF, JPG, PNG — max 5MB each)
            'documents'            => ['nullable', 'array', 'max:5'],
            'documents.*'          => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            // Emergency availability
            'is_emergency_available' => ['nullable', 'boolean'],
            'is_24_hours'            => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex'              => 'Password must contain at least one uppercase letter and one number.',
            'phone_number.regex'          => 'Please provide a valid phone number.',
            'phone.regex'                 => 'Please provide a valid phone number.',
            'latitude.between'            => 'Latitude must be between -90 and 90.',
            'longitude.between'           => 'Longitude must be between -180 and 180.',
            'license_number.unique'       => 'A vet profile with this license number already exists.',
            'email.unique'                => 'An account with this email already exists.',
            'accepted_species.min'        => 'Please specify at least one accepted species.',
            'services_offered.min'        => 'Please specify at least one service offered.',
            'years_of_experience.max'     => 'Years of experience cannot exceed 60.',
            'documents.max'               => 'You can upload a maximum of 5 documents.',
            'documents.*.mimes'           => 'Documents must be PDF, JPG, or PNG files.',
            'documents.*.max'             => 'Each document must not exceed 5MB.',
            'profile_photo.required'       => 'Profile photo is required.',

            'qualification.required'       => 'Qualification is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('phone_number') && $this->filled('phone')) {
            $this->merge(['phone_number' => $this->input('phone')]);
        }

        if (!$this->filled('phone') && $this->filled('phone_number')) {
            $this->merge(['phone' => $this->input('phone_number')]);
        }

        if (!$this->filled('qualification') && $this->filled('qualifications')) {
            $this->merge(['qualification' => $this->input('qualifications')]);
        }

        if (!$this->filled('qualifications') && $this->filled('qualification')) {
            $this->merge(['qualifications' => $this->input('qualification')]);
        }

        if (!$this->filled('specialization') && $this->filled('qualifications')) {
            $this->merge(['specialization' => $this->input('qualifications')]);
        }

        if ($this->has('location_override_confirmed')) {
            $this->merge([
                'location_override_confirmed' => filter_var(
                    $this->input('location_override_confirmed'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? false,
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $deviceLat = $this->input('device_latitude');
            $deviceLng = $this->input('device_longitude');
            $clinicLat = $this->input('latitude');
            $clinicLng = $this->input('longitude');

            if ($deviceLat === null || $deviceLng === null || $clinicLat === null || $clinicLng === null) {
                return;
            }

            $distanceKm = $this->haversineDistanceKm(
                (float) $deviceLat,
                (float) $deviceLng,
                (float) $clinicLat,
                (float) $clinicLng
            );

            if ($distanceKm > 10 && !$this->boolean('location_override_confirmed')) {
                $validator->errors()->add(
                    'location_override_confirmed',
                    "Clinic location is {$distanceKm} km away from your current location. Please confirm location override."
                );
            }
        });
    }

    private function haversineDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadiusKm * $c);
    }
}
