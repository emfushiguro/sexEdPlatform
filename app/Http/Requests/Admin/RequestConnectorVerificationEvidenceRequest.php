<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestConnectorVerificationEvidenceRequest extends FormRequest
{
    private const MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const EVIDENCE_CATEGORIES = [
        'organization_identity',
        'regulatory_eligibility',
        'representative_identity',
        'representative_authority',
        'supporting_evidence',
    ];

    private const METADATA_FIELDS = [
        'issuer',
        'document_number',
        'issue_date',
        'expiry_date',
    ];

    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    public function rules(): array
    {
        return [
            'submission_round' => ['required', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:255'],
            'guidance' => ['required', 'string', 'max:2000'],
            'evidence_category' => ['required', Rule::in(self::EVIDENCE_CATEGORIES)],
            'minimum_evidence_count' => ['required', 'integer', 'min:1', 'max:5'],
            'maximum_evidence_count' => ['required', 'integer', 'min:1', 'max:5', 'gte:minimum_evidence_count'],
            'max_kilobytes' => ['nullable', 'integer', 'min:1', 'max:10240'],
            'accepted_mime_types' => ['required', 'array', 'min:1', 'max:4'],
            'accepted_mime_types.*' => [Rule::in(self::MIME_TYPES)],
            'metadata_fields' => ['nullable', 'array'],
            'metadata_fields.*' => [Rule::in(self::METADATA_FIELDS)],
            'expires' => ['nullable', 'boolean'],
        ];
    }

    private function isAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->role === 'admin' || $user->hasRole('admin'));
    }
}
