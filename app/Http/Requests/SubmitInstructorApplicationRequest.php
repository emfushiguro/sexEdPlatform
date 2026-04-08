<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitInstructorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->isLearner()) {
            return false;
        }

        $hasPending = $user->instructorApplications()
            ->where('status', 'pending')
            ->exists();

        return ! $hasPending;
    }

    public function rules(): array
    {
        return [
            'government_id' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'clearance' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'cv_resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'educational_background' => ['required', 'string', 'in:high_school,college_undergrad,college_graduate,masters,doctorate,other'],
            'educational_background_other' => ['required_if:educational_background,other', 'nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'teaching_credential' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'sexed_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'professional_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'confirmation' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasTier2 = $this->hasFile('teaching_credential')
                || $this->hasFile('sexed_certificate')
                || $this->hasFile('professional_license');

            if (! $hasTier2) {
                $validator->errors()->add('tier2', 'Please upload at least one Tier 2 proof of expertise document.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'government_id.required' => 'A government-issued ID is required.',
            'clearance.required' => 'An NBI or police clearance is required.',
            'cv_resume.required' => 'A CV or resume is required.',
            'educational_background.required' => 'Please select your educational background.',
            'educational_background_other.required_if' => 'Please specify your educational background.',
            'confirmation.accepted' => 'You must confirm that the submitted information is authentic.',
        ];
    }
}
