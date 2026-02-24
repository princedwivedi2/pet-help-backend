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
            'status' => ['required', Rule::in(['confirmed', 'completed', 'cancelled', 'no_show'])],
            'reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:1000'],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'             => 'Status must be one of: confirmed, completed, cancelled, no_show.',
            'reason.required_if'    => 'A cancellation reason is required when cancelling an appointment.',
        ];
    }
}
