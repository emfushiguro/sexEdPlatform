<?php

namespace App\Http\Requests\Admin;

use App\Models\InstructorIdentityVerification;
use Illuminate\Foundation\Http\FormRequest;

class RequestInstructorIdentityActionRequest extends FormRequest
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
            'requested_action_codes' => ['required', 'array', 'min:1'],
            'requested_action_codes.*' => ['required', 'string', 'distinct', 'in:retry_persona,replace_evidence,clarify_personal_information'],
            'reason_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
