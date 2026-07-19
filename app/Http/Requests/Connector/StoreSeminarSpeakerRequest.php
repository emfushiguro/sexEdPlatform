<?php

namespace App\Http\Requests\Connector;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeminarSpeakerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1', 'max:20'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'role' => ['nullable', 'string', 'max:50'],
            'invitation_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('user_ids') && $this->filled('user_id')) {
            $this->merge(['user_ids' => [$this->input('user_id')]]);
        }
    }
}
