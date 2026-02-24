<?php

namespace App\Http\Requests\Api\V1\Vet;

use Illuminate\Foundation\Http\FormRequest;

class VetRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required when rejecting a vet application.',
        ];
    }
}
