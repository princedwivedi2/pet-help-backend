<?php

namespace App\Http\Requests\Api\V1\Pet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'species' => ['sometimes', 'required', Rule::in(['dog', 'cat', 'bird', 'rabbit', 'hamster', 'fish', 'reptile', 'other'])],
            'breed' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'weight_kg' => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'photo_url' => ['nullable', 'url', 'max:255'],
            'medical_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'species.in' => 'Species must be one of: dog, cat, bird, rabbit, hamster, fish, reptile, other.',
            'birth_date.before_or_equal' => 'Birth date cannot be in the future.',
        ];
    }
}
