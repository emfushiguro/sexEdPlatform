<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Foundation\Http\FormRequest;

class RegisterSeminarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
