<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('edit users');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,inactive,suspended,archived'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
