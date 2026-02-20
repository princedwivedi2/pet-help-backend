<?php

namespace App\Http\Requests\Api\V1\Sos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSosStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['cancelled', 'completed'])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be either cancelled or completed.',
        ];
    }
}
