<?php

namespace App\Http\Requests\Api\V1\Community;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityReplyRequest extends FormRequest
{
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

}
