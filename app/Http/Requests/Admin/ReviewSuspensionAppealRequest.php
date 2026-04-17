<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewSuspensionAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject', 'clarification_requested'])],
            'review_decision_notes' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }
}
