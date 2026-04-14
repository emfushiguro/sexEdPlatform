<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    private const ALLOWED_STANDARD_ROLES = ['admin', 'instructor', 'learner'];

    protected function prepareForValidation(): void
    {
        if ($this->boolean('apply_permission_overrides') && ! $this->has('direct_permissions')) {
            $this->merge(['direct_permissions' => []]);
        }

        $this->merge([
            'role' => trim((string) $this->input('role', '')),
            'new_role_name' => trim((string) $this->input('new_role_name', '')),
        ]);
    }

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
            'role' => ['required', 'string', 'max:100'],
            'new_role_name' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-\_ ]*$/i'],
            'new_role_permissions' => ['nullable', 'array'],
            'new_role_permissions.*' => ['required', 'string', 'exists:permissions,name'],
            'status' => ['required', 'in:active,inactive'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'apply_permission_overrides' => ['nullable', 'boolean'],
            'direct_permissions' => ['nullable', 'array'],
            'direct_permissions.*' => ['required', 'string', 'exists:permissions,name'],
            'permission_overrides_add' => ['nullable', 'array'],
            'permission_overrides_add.*' => ['required', 'string', 'exists:permissions,name'],
            'permission_overrides_remove' => ['nullable', 'array'],
            'permission_overrides_remove.*' => ['required', 'string', 'exists:permissions,name'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $role = strtolower((string) $this->input('role'));

            if ($role === 'others') {
                if ((string) $this->input('new_role_name') === '') {
                    $validator->errors()->add('new_role_name', 'Please provide a role name when selecting Others.');
                }
            } elseif (! in_array($role, self::ALLOWED_STANDARD_ROLES, true)) {
                $validator->errors()->add('role', 'Only Admin, Instructor, Learner, or Others are allowed.');
            } elseif (! $this->validateRoleExists($role)) {
                $validator->errors()->add('role', 'Selected role is not available.');
            }

            if (! $this->boolean('apply_permission_overrides')) {
                return;
            }

            if (! (bool) $this->user()?->can('manage permissions')) {
                $validator->errors()->add('direct_permissions', 'You are not allowed to set per-user permission overrides.');
            }
        });
    }

    private function validateRoleExists(string $role): bool
    {
        return \Spatie\Permission\Models\Role::query()
            ->where('name', $role)
            ->exists();
    }
}
