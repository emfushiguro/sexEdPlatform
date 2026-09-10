<?php

namespace App\Http\Requests\DependentSupport;

use App\Models\DependentSupportProfile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDependentSupportInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (DependentSupportProfile::CONTENT_FIELDS as $field) {
            $value = $this->input($field);
            $normalized[$field] = is_string($value)
                ? (trim($value) !== '' ? trim($value) : null)
                : $value;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'has_relevant_support_information' => ['required', 'boolean'],
            'relevant_health_considerations' => ['nullable', 'string', 'max:1000'],
            'accessibility_support_needs' => ['nullable', 'string', 'max:1000'],
            'additional_relevant_information' => ['nullable', 'string', 'max:1000'],
            'purpose_acknowledged' => $this->boolean('has_relevant_support_information')
                ? ['required', 'accepted']
                : ['nullable'],
            'expected_updated_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'medical_document' => ['prohibited'],
            'documents' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->boolean('has_relevant_support_information')) {
                return;
            }

            $hasContent = collect($this->supportPayload())->contains(
                fn ($value): bool => is_string($value) && $value !== '',
            );

            if (! $hasContent) {
                $validator->errors()->add(
                    'has_relevant_support_information',
                    'Provide at least one relevant support detail or choose Skip for now.',
                );
            }

            foreach (DependentSupportProfile::CONTENT_FIELDS as $field) {
                $value = $this->input($field);

                if (is_string($value) && strip_tags($value) !== $value) {
                    $validator->errors()->add($field, 'Use plain text only.');
                }
            }
        }];
    }

    public function supportPayload(): array
    {
        return collect(DependentSupportProfile::CONTENT_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $this->input($field)])
            ->all();
    }
}
