<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FinancialReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view financial reports');
    }

    public function rules(): array
    {
        return [
            'report_type' => ['nullable', 'string', 'in:weekly,monthly,yearly,custom'],
            'date_from' => ['nullable', 'date', 'required_if:report_type,custom'],
            'date_to' => ['nullable', 'date', 'required_if:report_type,custom', 'after_or_equal:date_from'],
            'timezone' => ['nullable', 'timezone'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
        ];
    }
}
