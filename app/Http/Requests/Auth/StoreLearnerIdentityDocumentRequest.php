<?php

namespace App\Http\Requests\Auth;

use App\Services\Identity\LearnerIdentityDocumentDraft;
use Illuminate\Validation\Rule;

class StoreLearnerIdentityDocumentRequest extends SubmitLearnerIdentityRequest
{
    protected function prepareForValidation(): void
    {
        $selection = (string) $this->input('id_selection');
        $subtype = str_starts_with($selection, 'government_id:') ? substr($selection, 14) : null;

        $this->merge([
            'document_type' => $subtype !== null ? 'government_id' : $selection,
            'government_id_type' => $subtype,
            'government_id_type_other' => $subtype === 'other' ? $this->input('government_id_type_other') : null,
        ]);
    }

    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['selfie'], $rules['confirm_submission']);

        $choices = array_map(fn (string $type) => 'government_id:'.$type, array_keys(config('guardian_identity.id_types', [])));
        $case = $this->user()?->identityVerifications()->whereNull('superseded_at')->first();
        if ($case?->pathway === 'teen') {
            array_unshift($choices, 'school_id', 'institution_id');
        }
        $rules['id_selection'] = ['required', Rule::in($choices)];

        $draft = $case ? app(LearnerIdentityDocumentDraft::class)->current($this->user(), $case) : null;
        if ($draft && ($draft['id_selection'] ?? null) === $this->input('id_selection')) {
            foreach (['identity_front', 'identity_back'] as $slot) {
                if (! empty($draft[$slot.'_path'])) {
                    $rules[$slot][0] = 'sometimes';
                }
            }
        }

        return $rules;
    }
}
