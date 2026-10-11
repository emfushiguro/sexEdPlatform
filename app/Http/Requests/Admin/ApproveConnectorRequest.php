<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'submission_round' => ['required', 'integer', 'min:1'],
            'checklist' => ['required', 'array'],
            'checklist.organization_identity_established' => ['required', 'accepted'],
            'checklist.regulatory_evidence_resolved' => ['required', 'accepted'],
            'checklist.representative_identity_verified' => ['required', 'accepted'],
            'checklist.representative_authority_established' => ['required', 'accepted'],
            'checklist.required_evidence_complete' => ['required', 'accepted'],
            'checklist.application_consistent' => ['required', 'accepted'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
