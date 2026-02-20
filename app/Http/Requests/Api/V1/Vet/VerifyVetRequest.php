<?php

namespace App\Http\Requests\Api\V1\Vet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class VerifyVetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in'          => 'Action must be either approve or reject.',
            'reason.required_if' => 'A reason is required when rejecting a vet.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
