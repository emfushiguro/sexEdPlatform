<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ChangeUserRoleRequest extends FormRequest
{
    private const ALLOWED_STANDARD_ROLES = ['admin', 'instructor', 'learner'];

    protected function prepareForValidation(): void
    {
        $reason = trim((string) $this->input('reason', ''));
        $customNotes = trim((string) $this->input('custom_notes', ''));

        $this->merge([
            'reason' => $reason === '' ? null : $reason,
            'custom_notes' => $customNotes === '' ? null : $customNotes,
        ]);
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('assign roles');
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:100'],
            'new_role_name' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9\-\_ ]*$/i'],
            'new_role_permissions' => ['nullable', 'array'],
            'new_role_permissions.*' => ['required', 'string', 'exists:permissions,name'],
            'reason' => ['nullable', 'string', 'max:500'],
            'custom_notes' => ['nullable', 'string', 'max:10000'],
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

                return;
            }

            if (! in_array($role, self::ALLOWED_STANDARD_ROLES, true)) {
                $validator->errors()->add('role', 'Only Admin, Instructor, Learner, or Others are allowed.');

                return;
            }

            if (! \Spatie\Permission\Models\Role::query()->where('name', $role)->exists()) {
                $validator->errors()->add('role', 'Selected role is not available.');
            }
        });
    }
}
