<?php

namespace App\Http\Requests\Chat;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'conversation_type' => ['required', 'string', Rule::in(Conversation::supportedConversationTypes())],
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'quiz_id' => ['nullable', 'integer', 'exists:quizzes,id'],
            'initial_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
