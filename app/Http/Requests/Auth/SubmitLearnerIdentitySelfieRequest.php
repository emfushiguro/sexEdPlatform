<?php

namespace App\Http\Requests\Auth;

class SubmitLearnerIdentitySelfieRequest extends SubmitLearnerIdentityRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $draft = session('learner_identity_document_draft', []);
        if (($draft['document_type'] ?? null) === 'government_id' && isset($rules['selfie'])) {
            $rules['selfie'][0] = 'required';
        }

        return array_intersect_key($rules, array_flip(['selfie', 'confirm_submission']));
    }
}
