<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealThreadMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_body' => ['required', 'string', 'min:2', 'max:5000'],
            'parent_message_id' => ['nullable', 'integer', 'exists:appeal_thread_messages,id'],
        ];
    }
}
