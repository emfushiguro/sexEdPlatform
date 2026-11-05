<?php

namespace App\Http\Requests\Admin;

use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'note' => [Rule::requiredIf($this->routeIs('admin.parent-verifications.relationships.reject') && $this->boolean('allow_resubmission', true)), 'nullable', 'required_if:reason_code,other', 'string', 'max:1000'],
            'allow_resubmission' => ['nullable', 'boolean'],
            'current_evidence_round' => [Rule::requiredIf($this->routeIs('admin.parent-verifications.relationships.reject')), 'nullable', 'integer', 'min:0'],
            'requested_document_ids' => ['nullable', 'array', 'max:10'],
            'requested_document_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'requested_document_types' => ['nullable', 'array', 'max:10'],
            'requested_document_types.*' => ['required', 'string', 'distinct', Rule::in(GuardianRelationshipTypes::acceptedDocumentTypes($this->route('parentChildAccount')?->relationship_type))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->routeIs('admin.parent-verifications.relationships.reject')) {
                return;
            }
            if ($this->boolean('allow_resubmission', true)
                && empty($this->input('requested_document_ids')) && empty($this->input('requested_document_types'))) {
                $validator->errors()->add('requested_document_ids', 'Select current evidence to replace or a missing document type.');
            }
            if ((int) $this->input('current_evidence_round', -1) !== (int) $this->route('parentChildAccount')?->current_evidence_round) {
                $validator->errors()->add('current_evidence_round', 'This review is out of date. Reload the current evidence.');
            }
        }];
    }
}
