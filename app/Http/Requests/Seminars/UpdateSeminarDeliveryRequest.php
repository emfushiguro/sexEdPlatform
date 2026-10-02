<?php

namespace App\Http\Requests\Seminars;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Throwable;

class UpdateSeminarDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        $data = parent::validationData();
        foreach (['external_link_visible_at', 'external_link_expires_at'] as $field) {
            if (blank($data[$field] ?? null)) {
                continue;
            }
            try {
                $data[$field] = Carbon::parse($data[$field], config('app.display_timezone'))->utc()->format('Y-m-d H:i:s');
            } catch (Throwable) {
                // Validation reports malformed dates.
            }
        }

        return $data;
    }

    public function rules(): array
    {
        return [
            'external_url' => ['sometimes', 'required', 'url'],
            'external_link_visible_at' => ['sometimes', 'nullable', 'date'],
            'external_link_expiry_mode' => ['sometimes', Rule::in(['ongoing', 'event_end', 'custom'])],
            'external_link_expires_at' => ['sometimes', 'nullable', 'date'],
            'delivery_instructions' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $data = $validator->getData();
            $seminar = $this->route('seminar');
            $url = $data['external_url'] ?? $seminar?->external_url;
            if (is_string($url) && filled($url) && ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                $validator->errors()->add('external_url', 'The external URL must use HTTP or HTTPS.');
            }
            $release = $this->parsedDate(array_key_exists('external_link_visible_at', $data) ? $data['external_link_visible_at'] : $seminar?->external_link_visible_at);
            $mode = $data['external_link_expiry_mode'] ?? $seminar?->external_link_expiry_mode ?? 'ongoing';
            $expiry = $this->parsedDate(array_key_exists('external_link_expires_at', $data) ? $data['external_link_expires_at'] : $seminar?->external_link_expires_at);
            if ($mode === 'custom') {
                if (! $expiry || $expiry->lte($release ?? now())) {
                    $validator->errors()->add('external_link_expires_at', 'Link expiry must be after release.');
                }
            }
            if ($mode === 'event_end' && $release && $seminar?->ends_at && $release->gte($seminar->ends_at)) {
                $validator->errors()->add('external_link_visible_at', 'Link release must be before event end.');
            }
        });
    }

    private function parsedDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
