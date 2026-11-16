<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSuspensionDurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['duration_amount' => ['required', 'integer', 'min:1', 'max:'.($this->input('duration_unit') === 'hours' ? 8760 : 365)],
            'duration_unit' => ['required', Rule::in(['hours', 'days'])],
            'reason' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
