<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use App\Support\ConnectorVerificationRequirements;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

    private function storeDocument(
        Connector $connector,
        ConnectorVerificationRequirement $requirement,
        UploadedFile $file,
        array $entry,
        User $applicant,
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
        $directory = 'connector-verifications/'.$connector->id.'/round-1';
        $path = Storage::disk($disk)->putFileAs($directory, $file, Str::uuid().'.'.$extension);

        if ($path === false) {
            throw new RuntimeException('Unable to store uploaded evidence.');
        }

        $storedPaths[] = [$disk, $path];
        $metadata = Arr::only($entry, ['issuer', 'document_number', 'issue_date', 'expiry_date']);

        return $connector->verificationDocuments()->create([
            'connector_verification_requirement_id' => $requirement->id,
            'uploaded_by' => $applicant->id,
            'submission_round' => 1,
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
