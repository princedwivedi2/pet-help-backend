<?php

namespace App\Http\Requests\Api\V1\Community;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_uuid' => 'required|exists:community_topics,uuid',
            'title'      => 'required|string|max:191|min:5',
            'content'    => 'required|string|min:10|max:10000',
        ];
    }

}
