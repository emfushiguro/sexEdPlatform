<?php

namespace App\Http\Requests\Connector;

use App\Enums\SeminarParticipantType;
use App\Enums\SeminarType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class StoreSeminarRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'registration_approval_mode' => $this->input('registration_approval_mode', 'auto_approve'),
        ]);
    }

    public function validationData(): array
    {
        $data = parent::validationData();

        foreach (['starts_at', 'ends_at'] as $field) {
            if (blank($data[$field] ?? null)) {
                continue;
            }

            try {
                $data[$field] = Carbon::parse($data[$field], config('app.display_timezone'))
                    ->utc()
                    ->format('Y-m-d H:i:s');
            } catch (Throwable) {
                // Leave invalid values unchanged so Laravel returns a validation error.
            }
        }

        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string'],
            'type' => ['required', Rule::in(array_column(SeminarType::cases(), 'value'))],
            'category' => ['required', Rule::in(array_keys(config('seminars.categories')))],
            'custom_category' => ['nullable', 'string', 'max:80', 'required_if:category,other'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_approval_mode' => ['required', Rule::in(['auto_approve', 'manual'])],
            'target_participants' => ['required', Rule::in(array_column(SeminarParticipantType::cases(), 'value'))],
            'learner_age_categories' => ['array'],
            'learner_age_categories.*' => [Rule::in(array_keys(config('seminars.learner_age_categories')))],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $target = (string) $this->input('target_participants');
            $type = (string) $this->input('type');
            $ageCategories = array_filter((array) $this->input('learner_age_categories', []));

            if (in_array($target, ['learners', 'learners_and_instructors'], true) && $ageCategories === []) {
                $validator->errors()->add('learner_age_categories', 'Select at least one learner age category.');
            }

            if ($type === 'physical' && trim((string) $this->input('location')) === '') {
                $validator->errors()->add('location', 'Physical seminars require a location.');
            }
        });
    }
}
