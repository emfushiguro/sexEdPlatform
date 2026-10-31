<?php

namespace App\Http\Requests\InstructorApplication;

use Illuminate\Foundation\Http\FormRequest;

class SaveInstructorApplicationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('saveDraft', \App\Models\InstructorApplication::class);
    }

    public function rules(): array
    {
        return [
            'government_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'educational_background' => ['nullable', 'string', 'in:high_school,college_undergrad,college_graduate,masters,doctorate,other'],
            'educational_background_other' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'teaching_credential' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'sexed_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'professional_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
