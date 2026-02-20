<?php

namespace App\Http\Requests\Api\V1\Incident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pet_id' => ['nullable', 'integer', 'exists:pets,id'],
            'status' => ['nullable', Rule::in(['open', 'in_treatment', 'resolved', 'follow_up_required'])],
            'from_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: open, in_treatment, resolved, follow_up_required.',
            'to_date.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}
