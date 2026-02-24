<?php

namespace App\Http\Requests\Api\V1\Vet;

use Illuminate\Foundation\Http\FormRequest;

class VetApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
