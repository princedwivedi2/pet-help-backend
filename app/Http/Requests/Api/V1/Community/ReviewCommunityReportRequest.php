<?php

namespace App\Http\Requests\Api\V1\Community;

use Illuminate\Foundation\Http\FormRequest;

class ReviewCommunityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'      => 'required|in:reviewed,dismissed',
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }

}
