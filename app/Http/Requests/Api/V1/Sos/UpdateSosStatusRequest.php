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
            'status' => ['required', Rule::in([
                'acknowledged', 'in_progress', 'cancelled', 'completed',
                'sos_accepted', 'vet_on_the_way', 'arrived',
                'treatment_in_progress', 'sos_completed', 'sos_cancelled',
            ])],
            'resolution_notes'      => ['nullable', 'string', 'max:2000'],
            'vet_latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'vet_longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'emergency_charge'      => ['nullable', 'numeric', 'min:0'],
            'distance_travelled_km' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid SOS status transition.',
        ];
    }
}
