<?php

namespace App\Http\Requests\InstructorApplication;

use App\Models\InstructorApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveInstructorApplicationDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('saveDraft', InstructorApplication::class);
    }

    public function rules(): array
    {
        return [
            'government_id' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'clearance' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'cv_resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $draft = $this->user()?->instructorApplications()
                ->where('status', 'draft')
                ->latest('id')
                ->first();

            foreach ([
                'government_id' => ['path' => 'government_id_path', 'message' => 'A government-issued ID is required.'],
                'clearance' => ['path' => 'clearance_path', 'message' => 'An NBI or police clearance is required.'],
                'cv_resume' => ['path' => 'cv_resume_path', 'message' => 'A CV or resume is required.'],
            ] as $field => $requirement) {
                if ($field === 'government_id' && $this->user()?->instructorIdentityVerifications()->current()->accessApproved()->exists()) {
                    continue;
                }

                if (! $this->hasFile($field) && ! filled($draft?->{$requirement['path']})) {
                    $validator->errors()->add($field, $requirement['message']);
                }
            }
        });
    }
}
