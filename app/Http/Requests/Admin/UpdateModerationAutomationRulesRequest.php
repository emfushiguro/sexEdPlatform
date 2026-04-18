<?php

namespace App\Http\Requests\Admin;

use App\Enums\EnforcementActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModerationAutomationRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.key' => ['required', 'string', 'max:100'],
            'rules.*.name' => ['required', 'string', 'max:160'],
            'rules.*.priority' => ['nullable', 'integer', 'min:1'],
            'rules.*.is_active' => ['nullable', 'boolean'],
            'rules.*.conditions' => ['required', 'array'],
            'rules.*.conditions.min_violation_count' => ['nullable', 'integer', 'min:0'],
            'rules.*.conditions.min_violation_points' => ['nullable', 'integer', 'min:0'],
            'rules.*.conditions.highest_severity_in' => ['nullable', 'array'],
            'rules.*.action_type' => ['required', Rule::in(EnforcementActionType::values())],
            'rules.*.severity_level' => ['nullable', Rule::in(['minor', 'moderate', 'major', 'critical'])],
            'rules.*.trigger_type' => ['nullable', Rule::in(['automatic', 'manual'])],
            'rules.*.metadata' => ['nullable', 'array'],
        ];
    }
}
