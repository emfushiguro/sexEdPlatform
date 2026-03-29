<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:2048',
            'age_bracket' => 'required|in:kids,teens,adults',
            'enrollment_mode' => 'required|in:auto,manual',
            'order' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'access_type' => 'nullable|in:free,paid',
            'price_amount' => 'nullable|numeric|min:0.01|required_if:access_type,paid',
            'price_currency' => 'nullable|string|size:3',
            'enrollment_limit' => 'nullable|integer|min:1',
        ];
    }
}
