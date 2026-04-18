<?php

namespace App\Http\Requests\Admin;

class FinancialReportExportRequest extends FinancialReportFilterRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('export financial reports');
    }
}
