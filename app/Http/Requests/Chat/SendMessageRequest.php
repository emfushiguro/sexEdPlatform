<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_body' => ['required', 'string', 'max:5000'],
            'retry_of' => ['nullable', 'string', 'max:100'],
        ];
    }
}
