<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModuleFeedbackReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isInstructor() ?? false;
    }

    public function rules(): array
    {
        return [
            'reply_content' => ['required', 'string', 'max:5000'],
        ];
    }
}
