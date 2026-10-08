<?php

namespace App\Http\Requests\DependentSupport;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGuardianSupportAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }
}
