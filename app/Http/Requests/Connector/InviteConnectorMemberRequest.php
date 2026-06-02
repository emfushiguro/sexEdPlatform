<?php

namespace App\Http\Requests\Connector;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteConnectorMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'connector_role_id' => ['required', 'integer', Rule::exists('connector_roles', 'id')],
        ];
    }
}
