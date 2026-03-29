<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module_id' => 'nullable|exists:modules,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'passing_score' => 'required|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'attempt_limit' => 'nullable|integer|min:1',
            'time_limit_hours' => 'nullable|integer|min:0',
            'time_limit_minutes' => 'nullable|integer|min:0|max:59',
            'time_limit_seconds' => 'nullable|integer|min:0|max:59',
        ];
    }
}
