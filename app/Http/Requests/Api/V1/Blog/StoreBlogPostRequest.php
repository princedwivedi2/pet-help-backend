<?php

namespace App\Http\Requests\Api\V1\Blog;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreBlogPostRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => 'required|string|max:191',
            'excerpt'        => 'nullable|string|max:500',
            'content'        => 'required|string|min:50',
            'featured_image' => 'nullable|url|max:500',
            'category_id'    => 'nullable|exists:blog_categories,id',
            'status'         => 'sometimes|in:draft,published',
            'tags'           => 'nullable|array|max:10',
            'tags.*'         => 'string|max:50',
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
