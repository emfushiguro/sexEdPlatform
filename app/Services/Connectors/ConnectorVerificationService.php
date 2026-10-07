<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use App\Support\ConnectorVerificationRequirements;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class ConnectorVerificationService
{
    public function __construct(
        private readonly ConnectorVerificationRequirements $registry,
        private readonly ConnectorRoleService $roles,
    ) {}

    public function registerInitialApplication(User $applicant, array $data): Connector
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($applicant, $data, &$storedPaths): Connector {
                $organizationType = (string) $data['organization_type'];
                $category = (string) $data['category'];
                $definitions = $this->registry->snapshotFor($category, $organizationType);

                $this->assertNoActiveDuplicate($data);

                $connector = Connector::create([
                    'name' => $data['name'],
                    'slug' => $this->uniqueSlug($data['name']),
                    'category' => $category,
                    'organization_type' => $organizationType,
                    'organization_email' => $data['organization_email'] ?? null,
                    'contact_number' => $data['contact_number'],
                    'description' => $data['description'] ?? null,
                    'website_url' => $data['website_url'] ?? null,
                    'verification_notes' => $data['verification_notes'] ?? null,
                    'city_code' => $data['city_code'],
                    'barangay_code' => $data['barangay_code'],
                    'address_line' => $data['address_line'],
                    'status' => 'pending',
                    'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
                    'current_submission_round' => 1,
                    'verification_submitted_at' => now(),
                    'created_by' => $applicant->id,
                    'primary_representative_user_id' => $applicant->id,
                ]);

                $ownerRole = $this->roles->createDefaultOwnerRole($connector);

                $connector->memberships()->create([
                    'user_id' => $applicant->id,
                    'connector_role_id' => $ownerRole->id,
                    'status' => 'pending',
                ]);

                $evidence = is_array($data['evidence'] ?? null) ? $data['evidence'] : [];
                $identity = $this->approvedIdentity($applicant);
                $reuseIdentity = filter_var($data['reuse_representative_identity'] ?? false, FILTER_VALIDATE_BOOLEAN);

                foreach ($definitions as $definition) {
                    $key = $definition['key'];
                    $entry = is_array($evidence[$key] ?? null) ? $evidence[$key] : [];
                    $files = $this->filesFrom($entry);
                    $identityId = $reuseIdentity && $key === 'representative_identity'
                        ? $identity?->id
                        : null;

                    $requirement = $connector->verificationRequirements()->create([
                        'requirement_key' => $key,
                        'evidence_category' => $definition['evidence_category'],
                        'label' => $definition['label'],
                        'guidance' => $definition['guidance'],
                        'is_required' => $definition['required'],
                        'rule_group' => $definition['rule_group'],
                        'rule_mode' => $definition['rule_mode'],
                        'minimum_evidence_count' => $definition['minimum_evidence_count'],
                        'maximum_evidence_count' => $definition['maximum_evidence_count'],
                        'definition_snapshot' => $definition,
                        'source_snapshot' => Arr::only($definition, ['issuing_authority', 'source_url', 'source_reviewed_on']),
                        'status' => $identityId
                            ? ConnectorVerificationRequirement::STATUS_SATISFIED
                            : (! $definition['required'] && $definition['manual_not_applicable_allowed'] && $files === []
                                ? ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE
                                : ConnectorVerificationRequirement::STATUS_PENDING),
                        'learner_identity_verification_id' => $identityId,
                        'requested_by' => $applicant->id,
                    ]);

                    foreach ($files as $file) {
                        $document = $this->storeDocument(
                            $connector,
                            $requirement,
                            $file,
                            $entry,
                            $applicant,
                            1,
                            $storedPaths,
                        );

                        $connector->verificationAudits()->create([
                            'connector_verification_requirement_id' => $requirement->id,
                            'connector_verification_document_id' => $document->id,
                            'actor_id' => $applicant->id,
                            'action' => 'uploaded',
                            'new_state' => [
                                'status' => $document->status,
                                'mime_type' => $document->mime_type,
                                'size_bytes' => $document->size_bytes,
                                'content_sha256' => $document->content_sha256,
                            ],
                            'submission_round' => 1,
                        ]);
                    }
                }

                if ($data['logo'] ?? null) {
                    $connector->update(['logo_path' => $this->storeLogo($connector, $data['logo'], $storedPaths)]);
                }

                $connector->verificationAudits()->create([
                    'actor_id' => $applicant->id,
                    'action' => 'submitted',
                    'new_state' => [
                        'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
                        'current_submission_round' => 1,
                    ],
                    'submission_round' => 1,
                ]);

                $connector->reviews()->create([
                    'from_status' => null,
                    'to_status' => 'pending',
                    'reason' => 'Connector registration submitted.',
                    'reason_code' => 'submitted',
                    'submission_round' => 1,
                    'reviewed_at' => now(),
                ]);

                DB::afterCommit(function () use ($applicant, $connector): void {
                    $this->notifySafely($applicant, $connector->id, new ConnectorApplicationSubmittedNotification($connector));

                    try {
                        foreach ($this->adminRecipients() as $admin) {
                            $this->notifySafely($admin, $connector->id, new ConnectorApplicationSubmittedNotification($connector, 'admin'));
                        }
                    } catch (Throwable $e) {
                        Log::warning('Connector application admin notification lookup failed.', [
                            'connector_id' => $connector->id,
                            'exception' => $e,
                        ]);
                    }
                });

                return $connector->fresh(['roles.permissions', 'memberships']);
            });
        } catch (Throwable $e) {
            foreach ($storedPaths as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    public function resubmit(
        Connector $connector,
        User $applicant,
        array $submission,
        int $expectedRound,
    ): Connector {
        $this->assertApplicant($connector, $applicant);
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $connector,
                $applicant,
                $submission,
                $expectedRound,
                &$storedPaths,
            ): Connector {
                $locked = Connector::query()
                    ->whereKey($connector->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $locked->current_submission_round !== $expectedRound) {
                    throw new ConflictHttpException('This verification submission is stale. Refresh the status page and try again.');
                }

                $this->assertResubmissionAllowed($locked);
                $evidence = $this->submittedEvidence($submission);
                $requirements = $locked->verificationRequirements()
                    ->whereNull('superseded_at')
                    ->get();
                $legacy = $locked->status === 'pending'
                    && $locked->verification_status === null;

                if ($legacy && $requirements->isEmpty()) {
                    $organizationType = trim((string) ($submission['organization_type'] ?? ''));

                    if ($organizationType === '') {
                        throw ValidationException::withMessages([
                            'organization_type' => 'Choose the organization type before completing verification.',
                        ]);
                    }

                    $definitions = $this->registry->snapshotFor($locked->category, $organizationType);
                    $identity = filter_var(
                        $submission['reuse_representative_identity'] ?? false,
                        FILTER_VALIDATE_BOOLEAN,
                    ) ? $this->approvedIdentity($applicant) : null;

                    foreach ($definitions as $definition) {
                        $entry = is_array($evidence[$definition['key']] ?? null)
                            ? $evidence[$definition['key']]
                            : [];
                        $files = $this->filesFrom($entry);

                        $requirements->push($locked->verificationRequirements()->create([
                            'requirement_key' => $definition['key'],
                            'evidence_category' => $definition['evidence_category'],
                            'label' => $definition['label'],
                            'guidance' => $definition['guidance'],
                            'is_required' => $definition['required'],
                            'rule_group' => $definition['rule_group'],
                            'rule_mode' => $definition['rule_mode'],
                            'minimum_evidence_count' => $definition['minimum_evidence_count'],
                            'maximum_evidence_count' => $definition['maximum_evidence_count'],
                            'definition_snapshot' => $definition,
                            'source_snapshot' => Arr::only($definition, [
                                'issuing_authority',
                                'source_url',
                                'source_reviewed_on',
                            ]),
                            'status' => $identity && $definition['key'] === 'representative_identity'
                                ? ConnectorVerificationRequirement::STATUS_SATISFIED
                                : (! $definition['required']
                                    && $definition['manual_not_applicable_allowed']
                                    && $files === []
                                    ? ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE
                                    : ConnectorVerificationRequirement::STATUS_PENDING),
                            'learner_identity_verification_id' => $identity && $definition['key'] === 'representative_identity'
                                ? $identity->id
                                : null,
                            'requested_by' => $applicant->id,
                        ]));
                    }

                    $locked->organization_type = $organizationType;
                }

                if ($requirements->isEmpty()) {
                    throw ValidationException::withMessages([
                        'evidence' => 'No verification requirements are available for this application.',
                    ]);
                }

                $requirementsByKey = $requirements->keyBy('requirement_key');
                $legacyReuseIdentity = filter_var(
                    $submission['reuse_representative_identity'] ?? false,
                    FILTER_VALIDATE_BOOLEAN,
                );
                $submitted = [];
                $seenHashes = [];

                foreach ($evidence as $key => $entry) {
                    if (! is_array($entry)) {
                        throw ValidationException::withMessages([
                            'evidence' => 'Each verification requirement must be submitted as an object.',
                        ]);
                    }

                    if (! $requirementsByKey->has($key)) {
                        throw ValidationException::withMessages([
                            'evidence' => 'The submitted evidence type is not configured for this application.',
                        ]);
                    }

                    $requirement = $requirementsByKey->get($key);
                    $files = $this->filesFrom($entry);
                    $hasMetadata = collect(Arr::except($entry, ['files']))
                        ->filter(fn ($value) => $value !== null && $value !== '')
                        ->isNotEmpty();

                    if ($files === [] && ! $hasMetadata) {
                        continue;
                    }

                    if (! $legacy && ! $this->canReplaceRequirement($requirement)) {
                        throw ValidationException::withMessages([
                            'evidence' => 'Only rejected, expired, or administrator-requested evidence can be replaced.',
                        ]);
                    }

                    if ($legacyReuseIdentity && $key === 'representative_identity' && $files !== []) {
                        throw ValidationException::withMessages([
                            $key.'.files' => 'Do not upload identity evidence when reusing your approved case.',
                        ]);
                    }

                    $definition = $this->definitionFor($requirement);
                    $this->validateResubmissionEntry($definition, $entry, $files, $key);

                    foreach ($files as $file) {
                        $hash = $this->uploadedHash($file);

                        if (in_array($hash, $seenHashes[$requirement->id] ?? [], true)
                            || ConnectorVerificationDocument::query()
                                ->where('connector_id', $locked->id)
                                ->where('connector_verification_requirement_id', $requirement->id)
                                ->where('content_sha256', $hash)
                                ->where('status', '!=', ConnectorVerificationDocument::STATUS_SUPERSEDED)
                                ->exists()) {
                            throw ValidationException::withMessages([
                                $key.'.files' => 'This evidence file has already been submitted for this requirement.',
                            ]);
                        }

                        $seenHashes[$requirement->id][] = $hash;
                    }

                    $submitted[] = [
                        'entry' => $entry,
                        'files' => $files,
                        'requirement' => $requirement,
                    ];
                }

                if ($legacy) {
                    $this->assertLegacyEvidenceComplete(
                        $requirements,
                        $submitted,
                        $legacyReuseIdentity,
                    );
                } elseif ($submitted === []) {
                    throw ValidationException::withMessages([
                        'evidence' => 'Submit at least one eligible replacement document.',
                    ]);
                }

                $newRound = $expectedRound + 1;

                foreach ($submitted as $item) {
                    /** @var ConnectorVerificationRequirement $requirement */
                    $requirement = $item['requirement'];

                    $oldDocuments = $requirement->documents()
                        ->whereIn('status', [
                            ConnectorVerificationDocument::STATUS_REJECTED,
                            ConnectorVerificationDocument::STATUS_EXPIRED,
                        ])
                        ->get();

                    foreach ($oldDocuments as $oldDocument) {
                        $oldStatus = $oldDocument->status;
                        $oldDocument->update([
                            'status' => ConnectorVerificationDocument::STATUS_SUPERSEDED,
                            'superseded_at' => now(),
                        ]);

                        $locked->verificationAudits()->create([
                            'connector_verification_requirement_id' => $requirement->id,
                            'connector_verification_document_id' => $oldDocument->id,
                            'actor_id' => $applicant->id,
                            'action' => 'superseded',
                            'previous_state' => ['status' => $oldStatus],
                            'new_state' => ['status' => ConnectorVerificationDocument::STATUS_SUPERSEDED],
                            'submission_round' => $newRound,
                        ]);
                    }

                    $requirement->update([
                        'status' => ConnectorVerificationRequirement::STATUS_PENDING,
                        'superseded_at' => null,
                    ]);

                    foreach ($item['files'] as $file) {
                        $document = $this->storeDocument(
                            $locked,
                            $requirement,
                            $file,
                            $item['entry'],
                            $applicant,
                            $newRound,
                            $storedPaths,
                        );

                        $locked->verificationAudits()->create([
                            'connector_verification_requirement_id' => $requirement->id,
                            'connector_verification_document_id' => $document->id,
                            'actor_id' => $applicant->id,
                            'action' => 'uploaded',
                            'new_state' => [
                                'status' => $document->status,
                                'mime_type' => $document->mime_type,
                                'size_bytes' => $document->size_bytes,
                                'content_sha256' => $document->content_sha256,
                            ],
                            'submission_round' => $newRound,
                        ]);
                    }
                }

                $locked->update([
                    'current_submission_round' => $newRound,
                    'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
                    'verification_submitted_at' => now(),
                ]);

                $locked->verificationAudits()->create([
                    'actor_id' => $applicant->id,
                    'action' => 'resubmitted',
                    'previous_state' => [
                        'verification_status' => $connector->verification_status,
                        'current_submission_round' => $expectedRound,
                    ],
                    'new_state' => [
                        'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
                        'current_submission_round' => $newRound,
                    ],
                    'submission_round' => $newRound,
                ]);

                $locked->reviews()->create([
                    'from_status' => $locked->status,
                    'to_status' => $locked->status,
                    'reason' => 'Connector verification evidence resubmitted.',
                    'reason_code' => 'resubmitted',
                    'submission_round' => $newRound,
                    'reviewed_at' => now(),
                ]);

                DB::afterCommit(function () use ($applicant, $locked): void {
                    $current = $locked->fresh();
                    $this->notifySafely(
                        $applicant,
                        $current->id,
                        new ConnectorApplicationSubmittedNotification($current),
                    );

                    try {
                        foreach ($this->adminRecipients() as $admin) {
                            $this->notifySafely(
                                $admin,
                                $current->id,
                                new ConnectorApplicationSubmittedNotification($current, 'admin'),
                            );
                        }
                    } catch (Throwable $e) {
                        Log::warning('Connector verification admin notification lookup failed.', [
                            'connector_id' => $current->id,
                            'exception' => $e,
                        ]);
                    }
                });

                return $locked->fresh(['roles.permissions', 'memberships']);
            });
        } catch (Throwable $e) {
            foreach ($storedPaths as [$disk, $path]) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    private function storeDocument(
        Connector $connector,
        ConnectorVerificationRequirement $requirement,
        UploadedFile $file,
        array $entry,
        User $applicant,
        int $round,
        array &$storedPaths,
    ): ConnectorVerificationDocument {
        $mimeType = (string) $file->getMimeType();
        $hash = hash_file('sha256', $file->getRealPath());

        if ($hash === false) {
            throw new RuntimeException('Unable to hash uploaded evidence.');
        }

        $extension = match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
        $disk = (string) config('connector_verification.evidence_disk', 'local');
        $directory = 'connector-verifications/'.$connector->id.'/round-'.$round;
        $path = Storage::disk($disk)->putFileAs($directory, $file, Str::uuid().'.'.$extension);

        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded evidence.');
        }

        $storedPaths[] = [$disk, $path];
        $metadata = Arr::only($entry, ['issuer', 'document_number', 'issue_date', 'expiry_date']);

        return $connector->verificationDocuments()->create([
            'connector_verification_requirement_id' => $requirement->id,
            'uploaded_by' => $applicant->id,
            'submission_round' => $round,
            'disk' => $disk,
            'path' => $path,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $mimeType,
            'size_bytes' => (int) $file->getSize(),
            'content_sha256' => $hash,
            'issuer' => $metadata['issuer'] ?? null,
            'document_number' => $metadata['document_number'] ?? null,
            'issue_date' => $metadata['issue_date'] ?? null,
            'expiry_date' => $metadata['expiry_date'] ?? null,
            'status' => ConnectorVerificationDocument::STATUS_PENDING,
        ]);
    }

    private function assertApplicant(Connector $connector, User $applicant): void
    {
        if ($connector->created_by !== $applicant->id
            && $connector->primary_representative_user_id !== $applicant->id) {
            throw new AuthorizationException('Only the connector applicant can resubmit verification evidence.');
        }
    }

    private function assertResubmissionAllowed(Connector $connector): void
    {
        if (in_array($connector->status, ['withdrawn', 'suspended'], true)
            || in_array($connector->verification_status, [
                Connector::VERIFICATION_UNDER_REVIEW,
                Connector::VERIFICATION_REJECTED,
            ], true)) {
            throw new AuthorizationException('This connector is not eligible for verification resubmission.');
        }

        $allowed = $connector->verification_status === Connector::VERIFICATION_RESUBMISSION_REQUIRED
            || ($connector->status === 'verified'
                && $connector->verification_status === Connector::VERIFICATION_REVERIFICATION_REQUIRED)
            || ($connector->status === 'pending' && $connector->verification_status === null);

        if (! $allowed) {
            throw new AuthorizationException('This connector is not eligible for verification resubmission.');
        }
    }

    private function canReplaceRequirement(ConnectorVerificationRequirement $requirement): bool
    {
        return in_array($requirement->status, [
            ConnectorVerificationRequirement::STATUS_REJECTED,
            ConnectorVerificationRequirement::STATUS_EXPIRED,
        ], true) || (
            $requirement->status === ConnectorVerificationRequirement::STATUS_PENDING
            && $requirement->requested_by !== null
        );
    }

    private function definitionFor(ConnectorVerificationRequirement $requirement): array
    {
        $definition = is_array($requirement->definition_snapshot)
            ? $requirement->definition_snapshot
            : [];

        return array_replace([
            'key' => $requirement->requirement_key,
            'required' => (bool) $requirement->is_required,
            'rule_group' => $requirement->rule_group,
            'rule_mode' => $requirement->rule_mode,
            'minimum_evidence_count' => max(1, (int) $requirement->minimum_evidence_count),
            'maximum_evidence_count' => max(1, (int) $requirement->maximum_evidence_count),
            'accepted_mime_types' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
            ],
            'max_kilobytes' => 10240,
            'metadata_fields' => [],
            'manual_not_applicable_allowed' => false,
        ], $definition);
    }

    private function validateResubmissionEntry(
        array $definition,
        array $entry,
        array $files,
        string $key,
    ): void {
        $allowedMetadata = $definition['metadata_fields'] ?? [];

        foreach (array_keys(Arr::except($entry, ['files'])) as $field) {
            if (! in_array($field, $allowedMetadata, true)) {
                throw ValidationException::withMessages([
                    'evidence' => 'Evidence metadata is not configured for this requirement.',
                ]);
            }
        }

        if (count($files) < (int) $definition['minimum_evidence_count']) {
            throw ValidationException::withMessages([
                $key.'.files' => 'This evidence requires more files.',
            ]);
        }

        if (count($files) > (int) $definition['maximum_evidence_count']) {
            throw ValidationException::withMessages([
                $key.'.files' => 'Too many files were uploaded for this requirement.',
            ]);
        }

        $rawFiles = $entry['files'] ?? [];
        $rawFiles = $rawFiles instanceof UploadedFile ? [$rawFiles] : $rawFiles;

        if (! is_array($rawFiles) || collect($rawFiles)->contains(fn ($file) => ! $file instanceof UploadedFile)) {
            throw ValidationException::withMessages([
                $key.'.files' => 'Each evidence item must be an uploaded file.',
            ]);
        }

        foreach ($files as $file) {
            if (! $file->isValid()) {
                throw ValidationException::withMessages([
                    $key.'.files' => 'The uploaded evidence could not be read.',
                ]);
            }

            if (! in_array($file->getMimeType(), $definition['accepted_mime_types'], true)) {
                throw ValidationException::withMessages([
                    $key.'.files' => 'This file type is not accepted.',
                ]);
            }

            if ((int) $file->getSize() > ((int) $definition['max_kilobytes'] * 1024)) {
                throw ValidationException::withMessages([
                    $key.'.files' => 'This file is too large.',
                ]);
            }
        }

        foreach ($allowedMetadata as $field) {
            $value = $entry[$field] ?? null;

            if ($value !== null
                && $value !== ''
                && in_array($field, ['issue_date', 'expiry_date'], true)
                && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                throw ValidationException::withMessages([
                    $key.'.'.$field => 'Use the YYYY-MM-DD date format.',
                ]);
            }
        }
    }

    private function assertLegacyEvidenceComplete(
        $requirements,
        array $submitted,
        bool $reuseIdentity,
    ): void {
        $counts = [];
        $submittedByKey = collect($submitted)->keyBy(
            fn (array $item) => $item['requirement']->requirement_key,
        );

        foreach ($requirements as $requirement) {
            $definition = $this->definitionFor($requirement);
            $count = $submittedByKey->has($requirement->requirement_key)
                ? count($submittedByKey->get($requirement->requirement_key)['files'])
                : 0;

            if ($reuseIdentity && $requirement->requirement_key === 'representative_identity') {
                $count = 1;
            }

            $counts[$requirement->requirement_key] = $count;

            if ($definition['required']
                && $count < (int) $definition['minimum_evidence_count']) {
                throw ValidationException::withMessages([
                    $requirement->requirement_key.'.files' => 'This evidence is required to complete verification.',
                ]);
            }
        }

        foreach ($requirements->groupBy('rule_group') as $group) {
            $first = $group->first();
            $mode = $first->rule_mode ?? 'all';

            if ($mode === 'any'
                && ! $group->every(fn (ConnectorVerificationRequirement $requirement): bool => (bool) $this->definitionFor($requirement)['manual_not_applicable_allowed'])
                && ! $group->contains(fn (ConnectorVerificationRequirement $requirement): bool => $counts[$requirement->requirement_key] >= (int) $this->definitionFor($requirement)['minimum_evidence_count']
                )) {
                throw ValidationException::withMessages([
                    'evidence' => 'At least one configured verification document is required.',
                ]);
            }

            if ($mode === 'minimum') {
                $minimum = $group->map(
                    fn (ConnectorVerificationRequirement $requirement): int => (int) $this->definitionFor($requirement)['minimum_evidence_count'],
                )->max();

                if (array_sum(array_intersect_key($counts, $group->mapWithKeys(
                    fn (ConnectorVerificationRequirement $requirement): array => [$requirement->requirement_key => true],
                )->all())) < $minimum) {
                    throw ValidationException::withMessages([
                        'evidence' => 'The minimum verification evidence count has not been met.',
                    ]);
                }
            }
        }
    }

    private function submittedEvidence(array $submission): array
    {
        $input = $submission['evidence'] ?? [];

        return is_array($input) ? $input : [];
    }

    private function uploadedHash(UploadedFile $file): string
    {
        $hash = hash_file('sha256', $file->getRealPath());

        if ($hash === false) {
            throw new RuntimeException('Unable to hash uploaded evidence.');
        }

        return $hash;
    }

    private function storeLogo(Connector $connector, UploadedFile $logo, array &$storedPaths): string
    {
        $mimeType = (string) $logo->getMimeType();
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
        $disk = (string) config('connector_verification.logo_disk', 'public');
        $path = Storage::disk($disk)->putFileAs(
            'connector-logos/'.$connector->id,
            $logo,
            Str::uuid().'.'.$extension,
        );

        if ($path === false) {
            throw new RuntimeException('Unable to store connector logo.');
        }

        $storedPaths[] = [$disk, $path];

        return $path;
    }

    private function approvedIdentity(User $applicant): ?LearnerIdentityVerification
    {
        return LearnerIdentityVerification::query()
            ->where('user_id', $applicant->id)
            ->where('status', 'approved')
            ->whereNull('superseded_at')
            ->latest('approved_at')
            ->first();
    }

    private function filesFrom(array $entry): array
    {
        $files = $entry['files'] ?? [];

        return $files instanceof UploadedFile
            ? [$files]
            : (is_array($files) ? array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile)) : []);
    }

    private function assertNoActiveDuplicate(array $data): void
    {
        $query = Connector::query()
            ->whereIn('status', ['pending', 'verified', 'suspended'])
            ->lockForUpdate();

        if (! empty($data['organization_email'])) {
            $query->where('organization_email', $data['organization_email']);
        } else {
            $query
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $data['name'])])
                ->where('city_code', $data['city_code'])
                ->where('barangay_code', $data['barangay_code']);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'An active connector with these organization details already exists.',
            ]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'connector';
        $slug = $base;
        $i = 1;

        while (Connector::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function adminRecipients()
    {
        return User::query()
            ->where('role', 'admin')
            ->orWhereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();
    }

    private function notifySafely(User $user, int $connectorId, ConnectorApplicationSubmittedNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $e) {
            Log::warning('Connector application notification delivery failed.', [
                'connector_id' => $connectorId,
                'recipient_id' => $user->id,
                'exception' => $e,
            ]);
        }
    }
}
