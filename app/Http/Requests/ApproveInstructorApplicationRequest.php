<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveInstructorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'admin_message' => ['required', 'string', 'max:10000'],
            'review_application_id' => ['nullable', 'integer'],
        ];
    }
}
