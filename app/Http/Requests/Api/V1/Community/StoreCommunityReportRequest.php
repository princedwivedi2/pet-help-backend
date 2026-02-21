<?php

namespace App\Http\Requests\Api\V1\Community;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => 'required|in:post,reply',
            'reportable_uuid' => 'required|string',
            'reason'          => 'required|string|min:10|max:1000',
        ];
    }

}
