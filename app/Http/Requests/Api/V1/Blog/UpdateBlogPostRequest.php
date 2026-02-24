<?php

namespace App\Http\Requests\Api\V1\Blog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array  
    {
        return [
            'title'          => 'sometimes|string|max:191',
            'excerpt'        => 'nullable|string|max:500',
            'content'        => 'sometimes|string|min:50',
            'featured_image' => 'nullable|url|max:500',
            'category_id'    => 'nullable|exists:blog_categories,id',
            'status'         => 'sometimes|in:draft,published',
            'tags'           => 'nullable|array|max:10',
            'tags.*'         => 'string|max:50',
        ];
    }

}
