<?php

namespace App\Http\Requests;

use App\Models\InstructorApplication;
use App\Services\InstructorApplication\InstructorIdentityService;
use DomainException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class SubmitInstructorApplicationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if (! $this->routeIs('learner.instructor.apply.submit') || ! $user?->isLearner()
            || $user->isParentRegistration() || ! config('instructor_application.identity_enforced_from')
            || $user->instructorApplications()->where('status', 'pending')->exists()
            || $user->instructorIdentityVerifications()->current()->accessApproved()->exists()) {
            return;
        }

        try {
            app(InstructorIdentityService::class)->satisfyFromApprovedLearnerIdentity($user);
        } catch (DomainException $exception) {
            throw new HttpResponseException(redirect()->route('learner.dashboard')->with('error', $exception->getMessage()));
        }
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('submit', InstructorApplication::class);
    }

    protected function failedAuthorization(): void
    {
        $user = $this->user();
        if ($this->routeIs('learner.instructor.apply.submit') && $user?->isLearner()) {
            if ($user->instructorApplications()->where('status', 'pending')->exists()) {
                throw new HttpResponseException(redirect()->route('learner.instructor.submitted')
                    ->with('info', 'You already have a pending instructor application.'));
            }

            $message = $user->isParentRegistration()
                ? 'Guardian-registration accounts cannot apply as instructors in this workflow.'
                : 'Your account is not eligible to submit an instructor application. Check your age, email verification, and identity approval.';
            throw new HttpResponseException(redirect()->route('learner.dashboard')->with('error', $message));
        }

        parent::failedAuthorization();
    }

    public function rules(): array
    {
        return [
            'government_id' => [$this->requiresFile('government_id') ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'clearance' => [$this->requiresFile('clearance') ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'cv_resume' => [$this->requiresFile('cv_resume') ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'educational_background' => ['required', 'string', 'in:high_school,college_undergrad,college_graduate,masters,doctorate,other'],
            'educational_background_other' => ['required_if:educational_background,other', 'nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'teaching_credential' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'sexed_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'professional_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'confirmation' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasTier2 = $this->hasFile('teaching_credential')
                || $this->hasFile('sexed_certificate')
                || $this->hasFile('professional_license')
                || filled($this->draft()?->teaching_credential_path)
                || filled($this->draft()?->sexed_certificate_path)
                || filled($this->draft()?->professional_license_path);

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

    private function draft(): ?InstructorApplication
    {
        return $this->user()?->instructorApplications()->where('status', 'draft')->latest('id')->first();
    }

    private function requiresFile(string $field): bool
    {
        if ($field === 'government_id'
            && $this->user()?->instructorIdentityVerifications()->current()->accessApproved()->exists()) {
            return false;
        }

        return ! filled($this->draft()?->{$field.'_path'});
    }
}
