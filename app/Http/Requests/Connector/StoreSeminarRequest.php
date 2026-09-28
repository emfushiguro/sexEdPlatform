<?php

namespace App\Http\Requests\Connector;

use App\Services\Seminars\SeminarPublicationValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
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

        foreach (['starts_at', 'ends_at', 'registration_deadline_at', 'external_link_visible_at', 'external_link_expires_at', 'attendance_start_at', 'attendance_end_at'] as $field) {
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
        return SeminarPublicationValidator::rules(creating: true);
    }

    public function withValidator($validator): void
    {
        $validator->after(fn ($validator) => SeminarPublicationValidator::addContextErrors(
            $validator,
            $this->route('seminar')?->isNativeDelivery() === true
                && $this->input('type') === 'webinar'
                && $this->input('event_format') === 'native'
        ));
    }
}
