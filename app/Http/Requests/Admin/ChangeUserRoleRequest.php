<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangeUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage roles');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'in:learner,instructor,counselor,clinic,organization,admin'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
