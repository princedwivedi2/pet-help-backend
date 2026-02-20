<?php

namespace App\Http\Requests\Api\V1\Community;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreCommunityVoteRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'votable_type' => 'required|in:post,reply',
            'votable_uuid' => 'required|string',
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
