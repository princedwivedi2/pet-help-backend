<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OtpSendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'in:email,sms'],
            'purpose' => ['nullable', 'string', 'max:50'],
        ];
    }
}