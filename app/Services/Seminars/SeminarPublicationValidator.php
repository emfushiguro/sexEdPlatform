<?php

namespace App\Services\Seminars;

use App\Enums\SeminarParticipantType;
use App\Models\Seminar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SeminarPublicationValidator
{
    public static function rules(bool $creating = false): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'purpose' => ['required', 'string'],
            'type' => ['required', Rule::in(['seminar', 'webinar'])],
            'event_format' => ['required', Rule::in(['in_person', 'external', 'native'])],
            'category' => ['required', Rule::in(array_keys(config('seminars.categories')))],
            'custom_category' => ['nullable', 'string', 'max:80', 'required_if:category,other'],
            'starts_at' => $creating ? ['required', 'date', 'after:now'] : ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_approval_mode' => ['required', Rule::in(['auto_approve', 'manual'])],
            'target_participants' => ['required', Rule::in(array_column(SeminarParticipantType::cases(), 'value'))],
            'learner_age_categories' => ['array'],
            'learner_age_categories.*' => [Rule::in(array_keys(config('seminars.learner_age_categories')))],
            'location' => ['exclude_unless:event_format,in_person', 'required', 'string', 'max:255'],
            'venue_address' => ['exclude_unless:event_format,in_person', 'required', 'string', 'max:255'],
            'venue_room' => ['exclude_unless:event_format,in_person', 'nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string'],
            'external_platform' => ['exclude_unless:event_format,external', 'required', Rule::in(['google_meet', 'zoom', 'microsoft_teams', 'google_classroom', 'other'])],
            'external_platform_name' => ['exclude_unless:event_format,external', 'nullable', 'string', 'max:255', 'required_if:external_platform,other'],
            'external_url' => ['exclude_unless:event_format,external', 'required', 'url'],
            'external_link_visible_at' => ['exclude_unless:event_format,external', 'nullable', 'date'],
            'external_link_expiry_mode' => ['exclude_unless:event_format,external', 'nullable', Rule::in(['ongoing', 'event_end', 'custom'])],
            'external_link_expires_at' => ['exclude_unless:event_format,external', 'nullable', 'date', 'required_if:external_link_expiry_mode,custom'],
            'registration_deadline_at' => ['nullable', 'date'],
            'attendance_start_at' => ['nullable', 'date'],
            'attendance_end_at' => ['nullable', 'date', 'after:attendance_start_at'],
        ];
    }

    public static function addContextErrors($validator, bool $allowNative): void
    {
        $data = $validator->getData();
        $type = $data['type'] ?? null;
        $format = $data['event_format'] ?? null;
        $allowed = ['seminar' => ['in_person', 'external'], 'webinar' => ['external']];

        if (! in_array($format, $allowed[$type] ?? [], true)
            && ! ($allowNative && $type === 'webinar' && $format === 'native')) {
            $validator->errors()->add('event_format', 'Choose a supported event type and format.');
        }

        if (in_array($data['target_participants'] ?? null, ['learners', 'learners_and_instructors'], true)
            && array_filter((array) ($data['learner_age_categories'] ?? [])) === []) {
            $validator->errors()->add('learner_age_categories', 'Select at least one learner age category.');
        }

        if ($format === 'external' && is_string($data['external_url'] ?? null) && filled($data['external_url'])) {
            $scheme = parse_url($data['external_url'], PHP_URL_SCHEME);
            if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
                $validator->errors()->add('external_url', 'The external URL must use HTTP or HTTPS.');
            }
        }

        $start = self::date($data['starts_at'] ?? null);
        $deadline = self::date($data['registration_deadline_at'] ?? null);
        if ($start && $deadline && $deadline->gte($start)) {
            $validator->errors()->add('registration_deadline_at', 'Registration deadline must be before the event starts.');
        }

        if ($format !== 'external') {
            return;
        }

        $release = self::date($data['external_link_visible_at'] ?? null);
        $expiryMode = $data['external_link_expiry_mode'] ?? 'ongoing';
        $expiry = self::date($data['external_link_expires_at'] ?? null);
        if ($expiryMode === 'custom' && $expiry && $expiry->lte($release ?? now())) {
            $validator->errors()->add('external_link_expires_at', 'Link expiry must be after release.');
        }
        if ($expiryMode === 'event_end' && $release && $release->gte(self::date($data['ends_at'] ?? null) ?? $release)) {
            $validator->errors()->add('external_link_visible_at', 'Link release must be before event end.');
        }
    }

    public function assertReady(Seminar $seminar): void
    {
        $data = $seminar->makeVisible('external_url')->toArray();
        $validator = Validator::make($data, self::rules());
        $validator->after(fn ($validator) => self::addContextErrors($validator, $seminar->isNativeDelivery()));
        abort_if($validator->fails(), 422, 'Complete the event details before review or publication.');
    }

    private static function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
