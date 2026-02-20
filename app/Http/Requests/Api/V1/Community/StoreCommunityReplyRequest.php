<?php

namespace App\Http\Requests\Api\V1\Community;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreCommunityReplyRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content'     => 'required|string|min:2|max:5000',
            'parent_uuid' => 'nullable|exists:community_replies,uuid',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, $this->validationError(
            'Validation failed',
            $validator->errors()->toArray()
        ));
    }
}
