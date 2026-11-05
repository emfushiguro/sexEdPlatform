<?php

namespace App\Http\Requests\Admin;

use App\Models\InstructorIdentityVerification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveInstructorIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $case = $this->route('instructorIdentityVerification');
        $user = $this->user();

        return $user !== null
            && $user->isAdmin()
            && $user->hasPermissionTo('review instructor identities')
            && $case instanceof InstructorIdentityVerification
            && (int) $case->user_id !== (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'submission_round' => ['required', 'integer', 'min:1'],
            'persona_inquiry_id' => ['required', 'integer', 'min:1', 'exists:persona_inquiries,id'],
            'declared_confirmed' => ['accepted'],
            'provider_confirmed' => ['accepted'],
            'sandbox_philippine_id_confirmed' => ['sometimes', 'accepted'],
            'sandbox_id_type_confirmed' => ['sometimes', 'accepted'],
            'sandbox_portraits_compared' => ['sometimes', 'accepted'],
            'mismatch_rationale' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $case = $this->route('instructorIdentityVerification');
        if (! $case instanceof InstructorIdentityVerification || ! $case->sandbox_manual_review) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            foreach ([
                'sandbox_philippine_id_confirmed',
                'sandbox_id_type_confirmed',
                'sandbox_portraits_compared',
            ] as $field) {
                if (! $this->boolean($field)) {
                    $validator->errors()->add($field, 'This confirmation is required for Sandbox review.');
                }
            }
        });
    }

}
