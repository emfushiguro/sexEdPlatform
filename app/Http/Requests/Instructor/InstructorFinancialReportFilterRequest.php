<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class InstructorFinancialReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view own financial reports');
    }

    public function rules(): array
    {
        return [
            'report_type' => ['nullable', 'string', 'in:weekly,monthly,yearly,custom'],
            'date_from' => ['nullable', 'date', 'required_if:report_type,custom'],
            'date_to' => ['nullable', 'date', 'required_if:report_type,custom', 'after_or_equal:date_from'],
            'timezone' => ['nullable', 'timezone'],
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['nullable', 'string', 'in:gcash,paymaya,grab_pay,card,billease,bank_transfer,paymongo'],
            'payout_status' => ['nullable', 'string', 'in:pending,payable,paid,reversed'],
        ];
    }
}
