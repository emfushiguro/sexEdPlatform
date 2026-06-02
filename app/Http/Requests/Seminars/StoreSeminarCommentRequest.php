<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeminarCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
