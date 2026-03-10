<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vet_uuid'           => ['required', 'string', 'exists:vet_profiles,uuid'],
            'pet_id'             => ['required', 'integer', Rule::exists('pets', 'id')->where('user_id', $this->user()->id)],
            'scheduled_at'       => ['required', 'date', 'after:now'],
            'duration_minutes'   => ['nullable', 'integer', 'min:15', 'max:120'],
            'reason'             => ['required', 'string', 'min:5', 'max:1000'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'appointment_type'   => ['nullable', Rule::in(['online', 'clinic_visit', 'home_visit'])],
            'is_emergency'       => ['nullable', 'boolean'],
            'photo_url'          => ['nullable', 'string', 'max:500'],
            'home_address'       => ['required_if:appointment_type,home_visit', 'nullable', 'string', 'max:500'],
            'home_latitude'      => ['required_if:appointment_type,home_visit', 'nullable', 'numeric', 'between:-90,90'],
            'home_longitude'     => ['required_if:appointment_type,home_visit', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'vet_uuid.exists'       => 'The selected vet profile does not exist.',
            'scheduled_at.after'    => 'The appointment must be scheduled in the future.',
            'reason.min'            => 'Please provide at least 5 characters describing the reason.',
            'duration_minutes.min'  => 'Minimum appointment duration is 15 minutes.',
            'duration_minutes.max'  => 'Maximum appointment duration is 120 minutes.',
        ];
    }
}
