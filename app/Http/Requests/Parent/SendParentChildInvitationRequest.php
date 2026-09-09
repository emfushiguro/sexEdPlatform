<?php

namespace App\Http\Requests\Parent;

use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendParentChildInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $relationshipType = (string) $this->input('relationship_type');

        return array_merge(
            GuardianRelationshipEvidenceRules::for(
                GuardianRelationshipTypes::acceptedDocumentTypes($relationshipType),
            ),
            [
                'identifier' => ['required', 'string', 'max:255'],
                'relationship_type' => ['required', Rule::in(GuardianRelationshipTypes::selectableValues())],
                'relationship_custom' => ['nullable', 'required_if:relationship_type,other', 'string', 'max:120'],
                'message' => ['nullable', 'string', 'max:500'],
                'confirm_relationship_verification' => ['accepted'],
            ],
        );
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $documents = (array) $this->input('documents', []);

            foreach (GuardianRelationshipEvidenceRules::metadataErrors($documents) as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            $submittedTypes = collect($documents)->pluck('document_type')->filter()->all();
            $requiredTypes = GuardianRelationshipTypes::requiredDocumentTypes(
                (string) $this->input('relationship_type'),
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
