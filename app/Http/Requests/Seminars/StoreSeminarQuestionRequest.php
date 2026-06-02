<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeminarQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
        ];
    }
}
