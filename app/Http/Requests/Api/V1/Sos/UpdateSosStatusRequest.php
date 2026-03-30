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
                'sos_accepted', 'vet_on_the_way', 'arrived',
                'sos_in_progress', 'sos_completed', 'sos_cancelled',
                // Legacy compat
                'acknowledged', 'in_progress', 'cancelled', 'completed',
                'treatment_in_progress',
            ])],
            'resolution_notes'      => ['nullable', 'string', 'max:2000'],
            'vet_latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'vet_longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'emergency_charge'      => ['nullable', 'numeric', 'min:0'],
            'distance_travelled_km' => ['nullable', 'numeric', 'min:0'],
            'response_type'         => ['nullable', Rule::in(['phone_guidance', 'come_to_clinic', 'home_visit'])],
            'estimated_arrival_at'  => ['nullable', 'date_format:Y-m-d\TH:i:sP'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid SOS status transition.',
        ];
    }
}
