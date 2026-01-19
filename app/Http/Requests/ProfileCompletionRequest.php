<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileCompletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtolower(trim($this->username)),
            'municipality' => trim($this->municipality),
            'bio' => $this->bio ? trim($this->bio) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->learnerProfile?->id;

        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('learner_profiles', 'username')->ignore($userId),
            ],
            'age_range' => [
                'required',
                'in:grade_4_up,grade_6_up,grade_8_up,grade_10_up,adult_18_plus'
            ],
            'gender' => [
                'nullable',
                'in:male,female,prefer_not_to_say'
            ],
            'municipality' => [
                'required',
                'string',
                'in:Alfonso,Amadeo,Bacoor,Carmona,Cavite City,Dasmariñas,General Emilio Aguinaldo,General Mariano Alvarez,General Trias,Imus,Indang,Kawit,Magallanes,Maragondon,Mendez,Naic,Noveleta,Rosario,Silang,Tagaytay,Tanza,Ternate,Trece Martires'
            ],
            'bio' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'username.regex' => 'Username can only contain lowercase letters, numbers, underscores, and hyphens.',
            'username.unique' => 'This username is already taken.',
            'municipality.in' => 'Please select a valid municipality in Cavite.',
        ];
    }
}
