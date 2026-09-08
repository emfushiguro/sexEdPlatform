<?php

namespace App\Http\Requests\Parent;

use App\Support\GuardianRelationshipTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendParentChildInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $relationshipType = (string) $this->input('relationship_type');

        return [
            'identifier' => ['required', 'string', 'max:255'],
            'relationship_type' => ['required', Rule::in(GuardianRelationshipTypes::selectableValues())],
            'relationship_custom' => ['nullable', 'required_if:relationship_type,other', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:500'],
            'relationship_document_type' => [
                'required',
                Rule::in(GuardianRelationshipTypes::acceptedDocumentTypes($relationshipType)),
            ],
            'relationship_document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],
            'relationship_supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'confirm_relationship_verification' => ['accepted'],
        ];
    }
}
