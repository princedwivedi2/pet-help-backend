<?php

namespace App\Http\Requests\Api\V1\Blog;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:2|max:2000',
        ];
    }

}
