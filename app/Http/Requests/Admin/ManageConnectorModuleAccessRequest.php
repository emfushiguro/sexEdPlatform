<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManageConnectorModuleAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:grant,revoke'],
            'user_ids' => ['required', 'array', 'min:1', 'max:100'],
            'user_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'confirmed' => $this->routeIs('admin.connectors.module-access.update') ? ['accepted'] : ['nullable'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.connectors.module-access.index', [
            'connector_id' => $this->route('connector')?->id,
            'entitlement_id' => $this->route('entitlement')?->id,
        ]);
    }
}
