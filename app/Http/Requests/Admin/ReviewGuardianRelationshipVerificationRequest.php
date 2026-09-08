<?php

namespace App\Http\Requests\Admin;

use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewGuardianRelationshipVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole('admin') || $this->user()?->can('manage user relationships'));
    }

    public function rules(): array
    {
        return [
            'reason_code' => ['required_without:approve', Rule::in(array_keys(GuardianRelationshipTypes::rejectionReasons()))],
            'note' => ['nullable', 'required_if:reason_code,other', 'string', 'max:1000'],
            'allow_resubmission' => ['nullable', 'boolean'],
        ];
    }
}
