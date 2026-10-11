<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CorrectConnectorModulePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:void,restore,retry_finalization'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
