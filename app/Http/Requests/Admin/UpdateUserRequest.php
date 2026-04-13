<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('edit users');
    }

    public function rules(): array
    {
        $targetUserId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($targetUserId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:learner,instructor,counselor,clinic,organization,admin'],
            'role_change_reason' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive,suspended,archived'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $routeUser = $this->route('user');
            $incomingRole = (string) $this->input('role');

            if (! $routeUser || $incomingRole === (string) $routeUser->role) {
                return;
            }

            $reason = trim((string) $this->input('role_change_reason', ''));

            if ($reason === '') {
                $validator->errors()->add('role_change_reason', 'Role change reason is required when changing role.');
            }
        });
    }
}
