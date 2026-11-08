<?php

namespace App\Http\Requests\Connector;

use App\Models\LearnerIdentityVerification;
use App\Support\ConnectorOptions;
use App\Support\ConnectorVerificationRequirements;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreConnectorRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $organizationType = $this->input('organization_type');

        if (is_string($organizationType) && in_array($organizationType, ConnectorOptions::categoryKeys(), true)) {
            $this->merge(['category' => $organizationType]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $cityTable = config('psgc.tables.cities', 'cities');
        $barangayTable = config('psgc.tables.barangays', 'barangays');

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(ConnectorOptions::categoryKeys())],
            'organization_type' => [
                'required',
                'string',
                Rule::in(array_keys(app(ConnectorVerificationRequirements::class)->organizationTypes())),
            ],
            'organization_type_detail' => ['required_if:organization_type,other', 'prohibited_unless:organization_type,other', 'string', 'max:255'],
            'organization_email' => ['nullable', 'email', 'max:255', Rule::unique('connectors', 'organization_email')],
            'contact_number' => ['required', 'string', 'max:30'],
            'city_code' => [
                'required',
                'string',
                Rule::exists($cityTable, 'code')->where(fn ($query) => $query
                    ->where('province_code', ConnectorOptions::CAVITE_PROVINCE_CODE)
                    ->orWhere('code', 'like', ConnectorOptions::CAVITE_CITY_CODE_PREFIX.'%')),
            ],
            'barangay_code' => [
                'required',
                'string',
                Rule::exists($barangayTable, 'code')->where(fn ($query) => $query->where('city_code', $this->input('city_code'))),
            ],
            'address_line' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'confirm_accuracy' => ['accepted'],
            'reuse_representative_identity' => ['nullable', 'boolean'],
            'evidence' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateEvidence($validator);
        });
    }

    private function validateEvidence(Validator $validator): void
    {
        $category = $this->input('category');
        $organizationType = $this->input('organization_type');
        $registry = app(ConnectorVerificationRequirements::class);

        if (! is_string($category) || ! is_string($organizationType)) {
            return;
        }

        try {
            $definitions = $registry->requirementsFor($category, $organizationType);
        } catch (\InvalidArgumentException) {
            $validator->errors()->add('organization_type', 'Choose a valid organization type for this category.');
            $validator->errors()->add('evidence', 'Verification evidence must match the selected organization type.');

            return;
        }

        $submitted = $this->submittedEvidence();
        $allowedKeys = array_column($definitions, 'key');

        foreach (array_keys($submitted) as $key) {
            if (! in_array($key, $allowedKeys, true)) {
                $validator->errors()->add('evidence', 'The submitted evidence type is not configured for this organization.');
            }
        }

        $reuseIdentity = $this->boolean('reuse_representative_identity');
        if ($reuseIdentity && ! $this->approvedIdentity()) {
            $validator->errors()->add(
                'reuse_representative_identity',
                'Only your current approved identity verification can be reused.',
            );
        }

        $counts = [];
        $groups = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $entry = is_array($submitted[$key] ?? null) ? $submitted[$key] : [];
            $files = $this->filesFrom($entry);
            $counts[$key] = $reuseIdentity && $key === 'representative_identity' ? 1 : count($files);

            if ($reuseIdentity && $key === 'representative_identity' && $files !== []) {
                $validator->errors()->add($key.'.files', 'Do not upload identity evidence when reusing your approved case.');
            }

            $this->validateEntry($validator, $definition, $entry, $files);

            if ($definition['required'] && $counts[$key] < $definition['minimum_evidence_count']) {
                $validator->errors()->add($key.'.files', 'This evidence is required.');
                $validator->errors()->add('evidence', 'Required verification evidence is missing.');
            }

            $groups[$definition['rule_group']][] = $definition;
        }

        foreach ($groups as $group) {
            $mode = $group[0]['rule_mode'] ?? 'all';

            if ($mode === 'any' && ! collect($group)->every(
                fn (array $definition): bool => $definition['manual_not_applicable_allowed']
            )) {
                $satisfied = collect($group)->contains(
                    fn (array $definition): bool => $counts[$definition['key']] >= $definition['minimum_evidence_count']
                );

                if (! $satisfied) {
                    $validator->errors()->add('evidence', 'At least one configured verification document is required.');
                }
            }

            if ($mode === 'minimum') {
                $total = collect($group)->sum(fn (array $definition): int => $counts[$definition['key']]);
                $minimum = collect($group)->max('minimum_evidence_count');

                if ($total < $minimum) {
                    $validator->errors()->add('evidence', 'The minimum verification evidence count has not been met.');
                }
            }
        }
    }

    private function validateEntry(
        Validator $validator,
        array $definition,
        array $entry,
        array $files,
    ): void {
        $allowedMetadata = $definition['metadata_fields'];

        foreach (array_keys($entry) as $field) {
            if ($field !== 'files' && ! in_array($field, $allowedMetadata, true)) {
                $validator->errors()->add('evidence', 'Evidence metadata is not configured for this requirement.');
            }
        }

        if (count($files) > $definition['maximum_evidence_count']) {
            $validator->errors()->add(
                $definition['key'].'.files',
                'Too many files were uploaded for this requirement.',
            );
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                $validator->errors()->add($definition['key'].'.files', 'Each evidence item must be an uploaded file.');

                continue;
            }

            if (! $file->isValid()) {
                $validator->errors()->add($definition['key'].'.files', 'The uploaded evidence could not be read.');

                continue;
            }

            if (! in_array($file->getMimeType(), $definition['accepted_mime_types'], true)) {
                $validator->errors()->add($definition['key'].'.files', 'This file type is not accepted.');
            }

            if ((int) $file->getSize() > $definition['max_kilobytes'] * 1024) {
                $validator->errors()->add($definition['key'].'.files', 'This file is too large.');
            }
        }

        foreach ($allowedMetadata as $field) {
            $value = $entry[$field] ?? null;

            if ($value !== null && $value !== '' && in_array($field, ['issue_date', 'expiry_date'], true) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                $validator->errors()->add($definition['key'].'.'.$field, 'Use the YYYY-MM-DD date format.');
            }
        }
    }

    private function submittedEvidence(): array
    {
        $input = $this->input('evidence', []);
        $files = $this->file('evidence', []);

        return array_replace_recursive(
            is_array($input) ? $input : [],
            is_array($files) ? $files : [],
        );
    }

    private function filesFrom(array $entry): array
    {
        $files = $entry['files'] ?? [];

        return $files instanceof UploadedFile
            ? [$files]
            : (is_array($files) ? array_values($files) : []);
    }

    private function approvedIdentity(): ?LearnerIdentityVerification
    {
        return LearnerIdentityVerification::query()
            ->where('user_id', $this->user()->id)
            ->where('status', 'approved')
            ->whereNull('superseded_at')
            ->latest('approved_at')
            ->first();
    }
}
