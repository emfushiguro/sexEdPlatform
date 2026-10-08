<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DetachParentChildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole('admin') || $this->user()?->can('manage user relationships'));
    }

    public function rules(): array
    {
        return [
            'parent_user_id' => ['required', 'integer', 'exists:users,id'],
            'child_user_id' => ['required', 'integer', 'exists:users,id', 'different:parent_user_id'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
