<?php

namespace App\Http\Requests\Api\V1\Vet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchVetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'available_only' => ['nullable', 'boolean'],
            'emergency_only' => ['nullable', 'boolean'],
            'city' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort_by' => ['nullable', Rule::in(['distance', 'rating'])],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Latitude is required for searching nearby vets.',
            'lng.required' => 'Longitude is required for searching nearby vets.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'radius_km.max' => 'Search radius cannot exceed 100 km.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('available_only')) {
            $this->merge([
                'available_only' => filter_var($this->available_only, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        if ($this->has('emergency_only')) {
            $this->merge([
                'emergency_only' => filter_var($this->emergency_only, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
