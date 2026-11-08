<?php

namespace App\Http\Requests\InstructorApplication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPersonaInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isInstructorApplicant()
            && (bool) $this->user()?->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'government_id_type' => [
                'required', 'string', 'max:40',
                Rule::in(array_keys((array) config('persona.government_id_classes', []))),
            ],
        ];
    }
}
