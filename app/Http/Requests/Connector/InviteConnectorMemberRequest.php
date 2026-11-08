<?php

namespace App\Http\Requests\Connector;

use App\Support\AccountEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteConnectorMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => AccountEmail::normalize($this->input('email'))]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', Rule::exists('users', 'email')],
            'connector_role_id' => ['required', 'integer', Rule::exists('connector_roles', 'id')],
        ];
    }
}
