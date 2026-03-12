<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'    => ['required', Rule::in([
                'confirmed', 'completed', 'cancelled', 'no_show',
                'accepted', 'rejected', 'in_progress',
            ])],
            'reason'    => ['required_if:status,cancelled', 'required_if:status,rejected', 'nullable', 'string', 'max:1000'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'          => 'Status must be one of: confirmed, completed, cancelled, no_show, accepted, rejected, in_progress.',
            'reason.required_if' => 'A reason is required when cancelling or rejecting an appointment.',
        ];
    }
}
