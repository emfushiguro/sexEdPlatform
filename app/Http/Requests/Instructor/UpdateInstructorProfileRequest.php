<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstructorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:2000'],
            'educational_background' => ['nullable', 'string', 'max:255'],
            'educational_background_entries' => ['nullable', 'array'],
            'educational_background_entries.*.school_name' => ['nullable', 'string', 'max:255'],
            'educational_background_entries.*.degree_program' => ['nullable', 'string', 'max:255'],
            'educational_background_entries.*.graduation_date' => ['nullable', 'date'],
            'professional_background' => ['nullable', 'string', 'max:3000'],
            'primary_expertise' => ['nullable', 'string', 'max:255'],
            'expertise_tags' => ['nullable', 'array'],
            'expertise_tags.*' => ['string', 'max:100'],
            'years_experience' => ['nullable', 'integer', 'min:0'],
            'certifications' => ['nullable', 'array'],
            'certifications.*.title' => ['nullable', 'string', 'max:255'],
            'certifications.*.organization' => ['nullable', 'string', 'max:255'],
            'certifications.*.completion_date' => ['nullable', 'date'],
            'certifications.*.existing_attachment' => ['nullable', 'string', 'max:255'],
            'certifications.*.attachment' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }
}
