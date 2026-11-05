<?php

namespace App\Http\Requests\Auth;

use App\Services\Location\CaviteAddressResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInstructorApplicantPersonalInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'middle_initial' => $this->filled('middle_initial')
                ? strtoupper(trim((string) $this->input('middle_initial')))
                : null,
            'last_name' => trim((string) $this->input('last_name')),
            'city_code' => trim((string) $this->input('city_code')),
            'barangay_code' => trim((string) $this->input('barangay_code')),
        ]);
    }

    public function rules(): array
    {
        $minimumBirthdate = now()->subYears((int) config('instructor_application.minimum_age', 18))->format('Y-m-d');

        return [
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z.\s]+$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'suffix' => ['nullable', 'string', 'max:20', 'in:Jr.,Sr.,II,III,IV,V'],
            'birthdate' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:'.$minimumBirthdate,
                'after:'.now()->subYears(100)->format('Y-m-d'),
            ],
            'gender' => ['nullable', 'string', 'in:male,female,prefer_not_to_say'],
            'contact' => ['nullable', 'string', 'max:40'],
            'city_code' => ['required', 'string', 'max:32'],
            'barangay_code' => ['required', 'string', 'max:32'],
            'address_line' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['city_code', 'barangay_code'])) {
                return;
            }

            try {
                app(CaviteAddressResolver::class)->resolve(
                    (string) $this->input('city_code'),
                    (string) $this->input('barangay_code'),
                );
            } catch (\DomainException $exception) {
                $validator->errors()->add(
                    $exception->getCode() === 1 ? 'city_code' : 'barangay_code',
                    $exception->getMessage(),
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'birthdate.before_or_equal' => 'You must be at least 18 years old to apply as an instructor.',
            'birthdate.after' => 'Please enter a valid birthdate.',
        ];
    }
}
