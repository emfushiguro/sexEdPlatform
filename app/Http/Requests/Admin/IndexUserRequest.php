<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view users') || (bool) $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'segment' => ['nullable', 'string', Rule::in(['', 'learners', 'parents', 'instructors', 'admins', 'archived'])],
            'role' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive', 'suspended', 'archived'])],
            'account_type' => ['nullable', 'string', 'max:100'],
            'age_bracket' => ['nullable', 'string', Rule::in(['kids', 'teens', 'adults'])],
            'learner_scope' => ['nullable', 'string', Rule::in(['all', 'platform', 'instructor'])],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'date_preset' => ['nullable', 'string', Rule::in(['today', '7d', '30d'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        ];
    }
}
