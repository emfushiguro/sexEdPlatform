<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResolveConnectorVerificationRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->isAdmin();
    }

    public function rules(): array
    {
        return [
            'submission_round' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:2000'],
            'internal_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function isAdmin(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->role === 'admin' || $user->hasRole('admin'));
    }
}
