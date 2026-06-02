<?php

namespace App\Http\Requests\Connector;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeminarSpeakerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'speaker_type' => ['required', Rule::in(['platform', 'external'])],
            'user_id' => ['nullable', 'required_if:speaker_type,platform', 'integer', 'exists:users,id'],
            'display_name' => ['nullable', 'required_if:speaker_type,external', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'role' => ['nullable', 'string', 'max:50'],
        ];
    }
}
