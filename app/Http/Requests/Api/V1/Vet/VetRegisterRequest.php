<?php

namespace App\Http\Requests\Api\V1\Vet;

use Illuminate\Foundation\Http\FormRequest;

class VetRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'          => ['required', 'string', 'max:150'],
            'email'              => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'confirmed'],
            'phone_number'       => ['required', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/'],
            'clinic_name'        => ['required', 'string', 'max:200'],
            'clinic_address'     => ['required', 'string', 'max:300'],
            'latitude'           => ['required', 'numeric', 'between:-90,90'],
            'longitude'          => ['required', 'numeric', 'between:-180,180'],
            'qualifications'     => ['required', 'string', 'max:2000'],
            'license_number'     => ['required', 'string', 'max:100', 'unique:vet_profiles,license_number'],
            'years_of_experience'=> ['required', 'integer', 'min:0', 'max:60'],
            'accepted_species'   => ['required', 'array', 'min:1'],
            'accepted_species.*' => ['string', 'max:50'],
            'services_offered'   => ['required', 'array', 'min:1'],
            'services_offered.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex'              => 'Password must contain at least one uppercase letter and one number.',
            'phone_number.regex'          => 'Please provide a valid phone number.',
            'latitude.between'            => 'Latitude must be between -90 and 90.',
            'longitude.between'           => 'Longitude must be between -180 and 180.',
            'license_number.unique'       => 'A vet profile with this license number already exists.',
            'email.unique'                => 'An account with this email already exists.',
            'accepted_species.min'        => 'Please specify at least one accepted species.',
            'services_offered.min'        => 'Please specify at least one service offered.',
            'years_of_experience.max'     => 'Years of experience cannot exceed 60.',
        ];
    }

}
