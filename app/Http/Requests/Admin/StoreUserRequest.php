<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create users');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:learner,instructor,counselor,clinic,organization,admin'],
            'status' => ['required', 'in:active,inactive,suspended,archived'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
