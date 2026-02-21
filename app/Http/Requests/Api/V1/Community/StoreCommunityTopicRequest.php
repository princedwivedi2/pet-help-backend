<?php

namespace App\Http\Requests\Api\V1\Community;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:100',
            'is_active'   => 'sometimes|boolean',
        ];
    }

}
