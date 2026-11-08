<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartPersonaInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) $user?->isLearner() && (bool) $user?->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'string', Rule::in(['school_id', 'institution_id', 'government_id'])],
            'government_id_type' => ['nullable', 'string', 'max:40', 'required_if:document_type,government_id'],
        ];
    }
}
