<?php

namespace App\Http\Requests;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGuardianRelationshipVerificationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'documents' => GuardianRelationshipEvidenceRules::normalizePairingKeys(
                (array) $this->input('documents', []),
            ),
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
            && $user->parent_verification_status === 'approved'
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

            $documents = (array) $this->input('documents', []);
            foreach (GuardianRelationshipEvidenceRules::metadataErrors($documents) as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            $submittedTypes = collect($documents)->pluck('document_type')->filter()->all();
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
