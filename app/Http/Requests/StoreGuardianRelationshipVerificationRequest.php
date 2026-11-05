<?php

namespace App\Http\Requests;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipCorrectionDraft;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGuardianRelationshipVerificationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $relationship = $this->route('parentChildAccount');
        if ($relationship instanceof ParentChildAccount && $relationship->relationship_correction_request !== null) {
            app(GuardianRelationshipCorrectionDraft::class)->populate($this, $this->user(), $relationship);
            // Laravel may have cached files while middleware read the incoming request.
            $this->convertedFiles = null;
        }
        $documents = $this->input('documents', []);
        if (! is_array($documents)) {
            return;
        }
        foreach ($documents as $document) {
            if (! is_array($document)
                || ! is_string($document['document_type'] ?? '')
                || ! is_string($document['document_side'] ?? '')
                || (isset($document['pairing_key']) && ! is_string($document['pairing_key']))) {
                return;
            }
        }
        $this->merge([
            'documents' => GuardianRelationshipEvidenceRules::normalizePairingKeys($documents),
        ]);
    }

    public function authorize(): bool
    {
        $relationship = $this->route('parentChildAccount');
        $user = $this->user();

        return $relationship instanceof ParentChildAccount
            && $user instanceof User
            && (int) $relationship->parent_user_id === (int) $user->id
            && $user->status === User::STATUS_ACTIVE
            && $user->isParentVerificationApproved()
            && in_array($relationship->relationship_verified_status, [
                ParentChildAccount::VERIFICATION_PENDING,
                ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
            ], true);
    }

    public function rules(): array
    {
        $relationship = $this->route('parentChildAccount');
        $type = $relationship instanceof ParentChildAccount
            ? $relationship->relationship_type
            : null;

        return array_merge(
            GuardianRelationshipEvidenceRules::for(
                GuardianRelationshipTypes::acceptedDocumentTypes($type),
            ),
            [
                'current_evidence_round' => ['required', 'integer', 'min:0'],
                'documents.*.replacement_document_id' => ['nullable', 'integer', 'min:1', 'distinct'],
                'relationship_notes' => [
                    Rule::requiredIf(GuardianRelationshipTypes::requiresCircumstances($type)),
                    'nullable',
                    'string',
                    'max:1000',
                ],
                'confirm_submission' => ['accepted'],
            ],
        );
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $relationship = $this->route('parentChildAccount');
            if (! $relationship instanceof ParentChildAccount) {
                return;
            }

            if ((int) $this->input('current_evidence_round', -1) !== (int) $relationship->current_evidence_round) {
                $validator->errors()->add('current_evidence_round', 'This form is out of date. Reload the current evidence.');
            }
            if ($validator->errors()->hasAny(['documents', 'documents.*.document_type', 'documents.*.document_side', 'documents.*.pairing_key'])) {
                return;
            }

            $documents = (array) $this->input('documents', []);
            foreach (GuardianRelationshipEvidenceRules::metadataErrors($documents) as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            $submittedTypes = collect($documents)->pluck('document_type')->filter()->all();
            if ($relationship->relationship_correction_request !== null) {
                $retainedTypes = $relationship->verificationDocuments()
                    ->where('submission_round', $relationship->current_evidence_round)
                    ->whereNull('superseded_at')
                    ->whereNotIn('id', $relationship->relationship_correction_request['document_ids'])
                    ->pluck('document_type')->all();
                $submittedTypes = array_merge($submittedTypes, $retainedTypes);
            }
            $requiredTypes = GuardianRelationshipTypes::requiredDocumentTypes(
                $relationship->relationship_type,
            );

            if (array_intersect($requiredTypes, $submittedTypes) === []) {
                $validator->errors()->add(
                    'documents',
                    'At least one core document for this verification pathway is required.',
                );
            }
        }];
    }
}
