<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => ['sometimes', 'string', 'max:255'],
            'phone'  => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/'],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex'  => 'Please provide a valid phone number.',
            'avatar.image' => 'Avatar must be an image file.',
            'avatar.mimes' => 'Avatar must be a JPEG, PNG, JPG, or WebP file.',
            'avatar.max'   => 'Avatar must not exceed 2MB.',
            'latitude.between' => 'Latitude must be between -90 and 90.',
            'longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
