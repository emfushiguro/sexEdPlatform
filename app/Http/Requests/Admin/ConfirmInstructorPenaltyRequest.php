<?php

namespace App\Http\Requests\Admin;

use App\Enums\InstructorRestrictionAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmInstructorPenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(InstructorRestrictionAction::values())],
        ];
    }
}
