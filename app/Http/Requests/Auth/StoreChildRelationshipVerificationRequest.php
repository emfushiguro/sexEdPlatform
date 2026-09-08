<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreChildRelationshipVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $guardian = $this->user();

        return $guardian instanceof User
            && $guardian->status === User::STATUS_ACTIVE
            && $guardian->parent_verification_status === 'approved';
    }

    public function rules(): array
    {
        $type = (string) session('child_step1.relationship_type', '');

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
            $type = (string) session('child_step1.relationship_type', '');
            $documents = (array) $this->input('documents', []);

            foreach (GuardianRelationshipEvidenceRules::metadataErrors($documents) as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            $requiredTypes = GuardianRelationshipTypes::requiredDocumentTypes($type);
            $submittedTypes = collect($documents)->pluck('document_type')->filter()->all();

            if (array_intersect($requiredTypes, $submittedTypes) === []) {
                $validator->errors()->add(
                    'documents',
                    'At least one core document for this verification pathway is required.',
                );
            }
        }];
    }
}
