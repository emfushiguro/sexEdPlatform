<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitSeminarCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'regex:/^\d{8}$/']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(back()->withErrors($validator));
    }
}
