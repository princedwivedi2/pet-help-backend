<?php

namespace App\Http\Requests\Api\V1\Guide;

use Illuminate\Foundation\Http\FormRequest;

class IndexGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:emergency_categories,id'],
        ];
    }
}
