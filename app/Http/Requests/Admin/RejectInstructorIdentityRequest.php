<?php

namespace App\Http\Requests\Admin;

use App\Models\InstructorIdentityVerification;
use Illuminate\Foundation\Http\FormRequest;

class RejectInstructorIdentityRequest extends FormRequest
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
            'reason_code' => ['required', 'string', 'max:64', 'in:identity_mismatch,provider_unavailable,not_eligible,other'],
            'reason_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
