<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use App\Notifications\Connectors\ConnectorModerationDecisionNotification;
use App\Notifications\Connectors\ConnectorVerificationActionRequiredNotification;
use App\Support\ConnectorVerificationRequirements;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ConnectorVerificationService
{
    private const PERMANENT_REJECTION_REASON_CODES = [
        'ineligible',
        'fraudulent',
        'policy_violation',
        'duplicate',
        'unsupported',
        'other',
    ];

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
                    'organization_type_detail' => $organizationType === 'other' ? $data['organization_type_detail'] : null,
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
                    $this->notifySafely(
                        $applicant,
                        $connector->id,
                        new ConnectorApplicationSubmittedNotification($connector, 'learner', 'submitted'),
                    );

                    try {
                        foreach ($this->adminRecipients() as $admin) {
                            $this->notifySafely(
                                $admin,
                                $connector->id,
                                new ConnectorApplicationSubmittedNotification($connector, 'admin', 'submitted'),
                            );
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

    public function reviewDocument(
        Connector $connector,
        ConnectorVerificationDocument $document,
        User $admin,
        string $decision,
        int $expectedRound,
        ?string $applicantReason,
        ?string $internalNote,
    ): ConnectorVerificationDocument {
        return DB::transaction(function () use (
            $connector,
            $document,
            $admin,
            $decision,
            $expectedRound,
            $applicantReason,
            $internalNote,
        ): ConnectorVerificationDocument {
            if (! in_array($decision, ['accept', 'reject'], true)) {
                throw ValidationException::withMessages([
                    'decision' => 'Choose accept or reject.',
                ]);
            }

            $lockedConnector = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $lockedConnector->current_submission_round !== $expectedRound) {
                throw new ConflictHttpException('This verification review is stale. Refresh the admin workspace.');
            }

            $lockedRequirement = ConnectorVerificationRequirement::query()
                ->whereKey($document->connector_verification_requirement_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedDocument = ConnectorVerificationDocument::query()
                ->whereKey($document->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedRequirement->connector_id !== (int) $lockedConnector->id
                || (int) $lockedDocument->connector_id !== (int) $lockedConnector->id
                || (int) $lockedDocument->connector_verification_requirement_id !== (int) $lockedRequirement->id) {
                throw new NotFoundHttpException;
            }

            if ($lockedConnector->verification_status !== Connector::VERIFICATION_UNDER_REVIEW) {
                throw new ConflictHttpException('This connector is not currently under verification review.');
            }

            if ((int) $lockedDocument->submission_round !== (int) $lockedConnector->current_submission_round
                || $lockedDocument->status !== ConnectorVerificationDocument::STATUS_PENDING
                || $lockedDocument->superseded_at !== null) {
                throw new ConflictHttpException('This verification document is already finalized or from another submission round.');
            }

            if ($decision === 'reject' && trim((string) $applicantReason) === '') {
                throw ValidationException::withMessages([
                    'applicant_reason' => 'An applicant-facing reason is required when rejecting evidence.',
                ]);
            }

            $oldStatus = $lockedDocument->status;
            $newStatus = $decision === 'accept'
                ? ConnectorVerificationDocument::STATUS_ACCEPTED
                : ConnectorVerificationDocument::STATUS_REJECTED;

            $lockedDocument->update([
                'status' => $newStatus,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'applicant_rejection_reason' => $decision === 'reject'
                    ? trim((string) $applicantReason)
                    : null,
                'internal_review_note' => $internalNote !== null && trim($internalNote) !== ''
                    ? trim($internalNote)
                    : null,
            ]);

            $acceptedCount = $lockedRequirement->documents()
                ->whereNull('superseded_at')
                ->where('status', ConnectorVerificationDocument::STATUS_ACCEPTED)
                ->count();
            $pendingCount = $lockedRequirement->documents()
                ->whereNull('superseded_at')
                ->where('status', ConnectorVerificationDocument::STATUS_PENDING)
                ->count();
            $minimum = max(1, (int) $lockedRequirement->minimum_evidence_count);

            $requirementStatus = $acceptedCount >= $minimum
                ? ConnectorVerificationRequirement::STATUS_SATISFIED
                : ($pendingCount > 0
                    ? ConnectorVerificationRequirement::STATUS_PENDING
                    : ConnectorVerificationRequirement::STATUS_REJECTED);

            $lockedRequirement->update(['status' => $requirementStatus]);

            $lockedConnector->verificationAudits()->create([
                'connector_verification_requirement_id' => $lockedRequirement->id,
                'connector_verification_document_id' => $lockedDocument->id,
                'actor_id' => $admin->id,
                'action' => $decision === 'accept' ? 'document_accepted' : 'document_rejected',
                'previous_state' => ['status' => $oldStatus],
                'new_state' => [
                    'status' => $newStatus,
                    'requirement_status' => $requirementStatus,
                ],
                'submission_round' => $expectedRound,
                'applicant_reason' => $decision === 'reject' ? trim((string) $applicantReason) : null,
                'internal_notes' => $internalNote !== null && trim($internalNote) !== ''
                    ? trim($internalNote)
                    : null,
            ]);

            return $lockedDocument->fresh();
        });
    }

    public function markRequirementNotApplicable(
        Connector $connector,
        ConnectorVerificationRequirement $requirement,
        User $admin,
        int $expectedRound,
        string $reason,
        ?string $internalNote,
    ): ConnectorVerificationRequirement {
        return DB::transaction(function () use (
            $connector,
            $requirement,
            $admin,
            $expectedRound,
            $reason,
            $internalNote,
        ): ConnectorVerificationRequirement {
            $lockedConnector = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $lockedConnector->current_submission_round !== $expectedRound) {
                throw new ConflictHttpException('This verification review is stale. Refresh the admin workspace.');
            }

            $lockedRequirement = ConnectorVerificationRequirement::query()
                ->whereKey($requirement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedRequirement->connector_id !== (int) $lockedConnector->id) {
                throw new NotFoundHttpException;
            }

            if ($lockedConnector->verification_status !== Connector::VERIFICATION_UNDER_REVIEW) {
                throw new ConflictHttpException('This connector is not currently under verification review.');
            }

            $definition = $this->definitionFor($lockedRequirement);
            if (! ($definition['manual_not_applicable_allowed'] ?? false)) {
                throw new AuthorizationException('This requirement cannot be marked not applicable.');
            }

            if ($lockedRequirement->status !== ConnectorVerificationRequirement::STATUS_PENDING
                || $lockedRequirement->superseded_at !== null) {
                throw new ConflictHttpException('This verification requirement is already finalized.');
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'A reason is required when resolving a requirement as not applicable.',
                ]);
            }

            $lockedRequirement->update([
                'status' => ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE,
            ]);

            $lockedConnector->verificationAudits()->create([
                'connector_verification_requirement_id' => $lockedRequirement->id,
                'actor_id' => $admin->id,
                'action' => 'requirement_not_applicable',
                'new_state' => ['status' => ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE],
                'submission_round' => $expectedRound,
                'applicant_reason' => trim($reason),
                'internal_notes' => $internalNote !== null && trim($internalNote) !== ''
                    ? trim($internalNote)
                    : null,
            ]);

            return $lockedRequirement->fresh();
        });
    }

    public function requestSupplementalRequirement(
        Connector $connector,
        User $admin,
        array $definition,
        int $expectedRound,
    ): ConnectorVerificationRequirement {
        return DB::transaction(function () use ($connector, $admin, $definition, $expectedRound): ConnectorVerificationRequirement {
            $lockedConnector = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ((int) $lockedConnector->current_submission_round !== $expectedRound) {
                throw new ConflictHttpException('This verification review is stale. Refresh the admin workspace.');
            }

            if ($lockedConnector->verification_status !== Connector::VERIFICATION_UNDER_REVIEW) {
                throw new ConflictHttpException('This connector is not currently under verification review.');
            }

            if ($lockedConnector->organization_type !== 'other') {
                throw new AuthorizationException('Supplemental evidence is available only for Other organizations.');
            }

            $minimum = max(1, (int) ($definition['minimum_evidence_count'] ?? 1));
            $maximum = max(1, (int) ($definition['maximum_evidence_count'] ?? $minimum));
            $mimeTypes = array_values(array_unique(array_intersect(
                [
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ],
                is_array($definition['accepted_mime_types'] ?? null)
                    ? $definition['accepted_mime_types']
                    : [],
            )));
            $metadataFields = array_values(array_intersect(
                ['issuer', 'document_number', 'issue_date', 'expiry_date'],
                is_array($definition['metadata_fields'] ?? null)
                    ? $definition['metadata_fields']
                    : [],
            ));

            if (trim((string) ($definition['label'] ?? '')) === ''
                || trim((string) ($definition['guidance'] ?? '')) === ''
                || $maximum < $minimum
                || $mimeTypes === []) {
                throw ValidationException::withMessages([
                    'label' => 'A valid supplemental requirement definition is required.',
                ]);
            }

            $key = 'custom_'.Str::uuid()->toString();
            $snapshot = [
                'key' => $key,
                'label' => trim((string) $definition['label']),
                'guidance' => trim((string) $definition['guidance']),
                'evidence_category' => (string) $definition['evidence_category'],
                'required' => true,
                'rule_group' => $key,
                'rule_mode' => 'all',
                'minimum_evidence_count' => $minimum,
                'maximum_evidence_count' => $maximum,
                'accepted_mime_types' => $mimeTypes,
                'max_kilobytes' => min(10240, max(1, (int) ($definition['max_kilobytes'] ?? 10240))),
                'metadata_fields' => $metadataFields,
                'expires' => (bool) ($definition['expires'] ?? false),
                'manual_not_applicable_allowed' => false,
                'issuing_authority' => null,
                'source_url' => null,
                'source_reviewed_on' => null,
            ];

            $requirement = $lockedConnector->verificationRequirements()->create([
                'requirement_key' => $key,
                'evidence_category' => $snapshot['evidence_category'],
                'label' => $snapshot['label'],
                'guidance' => $snapshot['guidance'],
                'is_required' => true,
                'rule_group' => $snapshot['rule_group'],
                'rule_mode' => $snapshot['rule_mode'],
                'minimum_evidence_count' => $minimum,
                'maximum_evidence_count' => $maximum,
                'definition_snapshot' => $snapshot,
                'source_snapshot' => null,
                'status' => ConnectorVerificationRequirement::STATUS_PENDING,
                'requested_by' => $admin->id,
            ]);

            $lockedConnector->update([
                'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            ]);

            $lockedConnector->verificationAudits()->create([
                'connector_verification_requirement_id' => $requirement->id,
                'actor_id' => $admin->id,
                'action' => 'supplemental_requested',
                'new_state' => [
                    'requirement_key' => $key,
                    'label' => $snapshot['label'],
                    'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
                ],
                'submission_round' => $expectedRound,
            ]);

            $lockedConnector->reviews()->create([
                'reviewed_by' => $admin->id,
                'from_status' => $lockedConnector->status,
                'to_status' => $lockedConnector->status,
                'reason' => 'Supplemental verification evidence requested.',
                'reason_code' => 'supplemental_requested',
                'submission_round' => $expectedRound,
                'reviewed_at' => now(),
            ]);

            $requirementLabel = $requirement->label;
            DB::afterCommit(function () use ($lockedConnector, $requirementLabel): void {
                $current = $lockedConnector->fresh();
                $recipient = $current->primaryRepresentative()->first();

                if ($recipient) {
                    $this->notifySafely(
                        $recipient,
                        $current->id,
                        new ConnectorVerificationActionRequiredNotification(
                            $current,
                            'supplemental_requirement',
                            [$requirementLabel],
                        ),
                    );
                }

                try {
                    foreach ($this->adminRecipients() as $admin) {
                        $this->notifySafely(
                            $admin,
                            $current->id,
                            new ConnectorVerificationActionRequiredNotification(
                                $current,
                                'supplemental_requirement',
                                [$requirementLabel],
                                'admin',
                            ),
                        );
                    }
                } catch (Throwable $e) {
                    Log::warning('Connector supplemental notification admin lookup failed.', [
                        'connector_id' => $current->id,
                        'exception' => $e,
                    ]);
                }
            });

            return $requirement->fresh();
        });
    }

    public function approve(
        Connector $connector,
        User $admin,
        int $expectedRound,
        ?string $internalNote,
    ): Connector {
        return DB::transaction(function () use (
            $connector,
            $admin,
            $expectedRound,
            $internalNote,
        ): Connector {
            $locked = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertDecisionRound($locked, $expectedRound);

            if ($locked->status !== 'pending'
                || $locked->verification_status !== Connector::VERIFICATION_UNDER_REVIEW) {
                throw new ConflictHttpException('Only a pending connector under verification review can be approved.');
            }

            $requirements = $locked->verificationRequirements()
                ->whereNull('superseded_at')
                ->get();

            if ($requirements->isEmpty()) {
                throw new HttpException(422, 'No current verification requirements are available.');
            }

            $this->assertApprovalEvidence($locked, $requirements, $expectedRound);

            $fromStatus = $locked->status;
            $reviewedAt = now();

            $locked->update([
                'status' => 'verified',
                'verification_status' => Connector::VERIFICATION_VERIFIED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => $reviewedAt,
                'rejection_reason' => null,
                'suspended_at' => null,
            ]);

            $locked->memberships()
                ->where('user_id', $locked->primary_representative_user_id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'active',
                    'accepted_at' => $reviewedAt,
                ]);

            $locked->reviews()->create([
                'reviewed_by' => $admin->id,
                'from_status' => $fromStatus,
                'to_status' => 'verified',
                'reason' => 'Connector approved.',
                'reason_code' => 'approved',
                'internal_notes' => $this->cleanNote($internalNote),
                'submission_round' => $expectedRound,
                'reviewed_at' => $reviewedAt,
            ]);

            $locked->verificationAudits()->create([
                'actor_id' => $admin->id,
                'action' => 'connector_approved',
                'previous_state' => [
                    'status' => $fromStatus,
                    'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
                ],
                'new_state' => [
                    'status' => 'verified',
                    'verification_status' => Connector::VERIFICATION_VERIFIED,
                ],
                'submission_round' => $expectedRound,
                'internal_notes' => $this->cleanNote($internalNote),
            ]);

            DB::afterCommit(function () use ($locked): void {
                $current = $locked->fresh();
                $recipient = $current->primaryRepresentative()->first();

                if ($recipient) {
                    $this->notifySafely(
                        $recipient,
                        $current->id,
                        new ConnectorModerationDecisionNotification($current, 'verified'),
                    );
                }
            });

            return $locked->fresh(['roles.permissions', 'memberships']);
        });
    }

    public function requestResubmission(
        Connector $connector,
        User $admin,
        int $expectedRound,
        string $applicantReason,
        ?string $internalNote,
    ): Connector {
        return DB::transaction(function () use (
            $connector,
            $admin,
            $expectedRound,
            $applicantReason,
            $internalNote,
        ): Connector {
            $locked = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertDecisionRound($locked, $expectedRound);

            if ($locked->verification_status !== Connector::VERIFICATION_UNDER_REVIEW) {
                throw new ConflictHttpException('This connector is not currently under verification review.');
            }

            $requirements = $locked->verificationRequirements()
                ->whereNull('superseded_at')
                ->get();
            $rejectedRequirementLabels = $requirements
                ->filter(fn (ConnectorVerificationRequirement $requirement): bool => in_array($requirement->status, [
                    ConnectorVerificationRequirement::STATUS_REJECTED,
                    ConnectorVerificationRequirement::STATUS_EXPIRED,
                ], true))
                ->pluck('label')
                ->filter()
                ->values()
                ->all();

            $eligible = $requirements->contains(function (ConnectorVerificationRequirement $requirement) use ($admin): bool {
                return in_array($requirement->status, [
                    ConnectorVerificationRequirement::STATUS_REJECTED,
                    ConnectorVerificationRequirement::STATUS_EXPIRED,
                ], true) || (
                    $requirement->status === ConnectorVerificationRequirement::STATUS_PENDING
                    && (int) $requirement->requested_by === (int) $admin->id
                );
            });

            if (! $eligible) {
                throw new HttpException(422, 'No rejected, expired, or administrator-requested requirement is eligible for resubmission.');
            }

            $reason = trim($applicantReason);
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'applicant_reason' => 'An applicant-facing reason is required.',
                ]);
            }

            $note = $this->cleanNote($internalNote);
            $locked->update([
                'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            ]);

            $locked->reviews()->create([
                'reviewed_by' => $admin->id,
                'from_status' => $locked->status,
                'to_status' => $locked->status,
                'reason' => $reason,
                'reason_code' => 'resubmission_requested',
                'internal_notes' => $note,
                'submission_round' => $expectedRound,
                'reviewed_at' => now(),
            ]);

            $locked->verificationAudits()->create([
                'actor_id' => $admin->id,
                'action' => 'resubmission_requested',
                'previous_state' => [
                    'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
                ],
                'new_state' => [
                    'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
                ],
                'submission_round' => $expectedRound,
                'applicant_reason' => $reason,
                'internal_notes' => $note,
            ]);

            if ($rejectedRequirementLabels !== []) {
                DB::afterCommit(function () use ($locked, $rejectedRequirementLabels): void {
                    $current = $locked->fresh();
                    $recipient = $current->primaryRepresentative()->first();

                    if ($recipient) {
                        $this->notifySafely(
                            $recipient,
                            $current->id,
                            new ConnectorVerificationActionRequiredNotification(
                                $current,
                                'document_rejected',
                                $rejectedRequirementLabels,
                            ),
                        );
                    }
                });
            }

            return $locked->fresh(['roles.permissions', 'memberships']);
        });
    }

    public function permanentlyReject(
        Connector $connector,
        User $admin,
        int $expectedRound,
        string $reasonCode,
        string $applicantReason,
        ?string $internalNote,
    ): Connector {
        if (! in_array($reasonCode, self::PERMANENT_REJECTION_REASON_CODES, true)) {
            throw ValidationException::withMessages([
                'reason_code' => 'Choose a valid permanent rejection reason.',
            ]);
        }

        return DB::transaction(function () use (
            $connector,
            $admin,
            $expectedRound,
            $reasonCode,
            $applicantReason,
            $internalNote,
        ): Connector {
            $locked = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertDecisionRound($locked, $expectedRound);

            if ($locked->status === 'withdrawn'
                || $locked->status === 'rejected'
                || $locked->verification_status === Connector::VERIFICATION_REJECTED) {
                throw new ConflictHttpException('This connector decision is already finalized.');
            }

            $reason = trim($applicantReason);
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'applicant_reason' => 'An applicant-facing reason is required.',
                ]);
            }

            $fromStatus = $locked->status;
            $note = $this->cleanNote($internalNote);
            $locked->update([
                'status' => 'rejected',
                'verification_status' => Connector::VERIFICATION_REJECTED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
                'suspended_at' => null,
            ]);

            $locked->reviews()->create([
                'reviewed_by' => $admin->id,
                'from_status' => $fromStatus,
                'to_status' => 'rejected',
                'reason' => $reason,
                'reason_code' => 'permanently_rejected',
                'internal_notes' => $note,
                'submission_round' => $expectedRound,
                'reviewed_at' => now(),
            ]);

            $locked->verificationAudits()->create([
                'actor_id' => $admin->id,
                'action' => 'connector_permanently_rejected',
                'previous_state' => [
                    'status' => $fromStatus,
                    'verification_status' => $connector->verification_status,
                ],
                'new_state' => [
                    'status' => 'rejected',
                    'verification_status' => Connector::VERIFICATION_REJECTED,
                    'reason_code' => $reasonCode,
                ],
                'submission_round' => $expectedRound,
                'applicant_reason' => $reason,
                'internal_notes' => $note,
            ]);

            DB::afterCommit(function () use ($locked): void {
                $current = $locked->fresh();
                $recipient = $current->primaryRepresentative()->first();

                if ($recipient) {
                    $this->notifySafely(
                        $recipient,
                        $current->id,
                        new ConnectorModerationDecisionNotification($current, 'rejected'),
                    );
                }
            });

            return $locked->fresh(['roles.permissions', 'memberships']);
        });
    }

    public function suspend(Connector $connector, User $admin, string $reason): Connector
    {
        return DB::transaction(function () use ($connector, $admin, $reason): Connector {
            $locked = Connector::query()
                ->whereKey($connector->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cleanReason = trim($reason);
            if ($cleanReason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'A suspension reason is required.',
                ]);
            }

            $fromStatus = $locked->status;
            $locked->update([
                'status' => 'suspended',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => $cleanReason,
                'suspended_at' => now(),
            ]);

            $locked->reviews()->create([
                'reviewed_by' => $admin->id,
                'from_status' => $fromStatus,
                'to_status' => 'suspended',
                'reason' => $cleanReason,
                'reason_code' => 'suspended',
                'submission_round' => (int) $locked->current_submission_round,
                'reviewed_at' => now(),
            ]);

            $locked->verificationAudits()->create([
                'actor_id' => $admin->id,
                'action' => 'connector_suspended',
                'previous_state' => ['status' => $fromStatus],
                'new_state' => ['status' => 'suspended'],
                'submission_round' => (int) $locked->current_submission_round,
                'applicant_reason' => $cleanReason,
            ]);

            return $locked->fresh(['roles.permissions', 'memberships']);
        });
    }

    public function recordDocumentAccess(
        ConnectorVerificationDocument $document,
        User $viewer,
        string $action,
    ): void {
        if (! in_array($action, ['document_previewed', 'document_downloaded'], true)) {
            throw new \InvalidArgumentException('Unsupported verification document audit action.');
        }

        $document->connector()->firstOrFail()->verificationAudits()->create([
            'connector_verification_requirement_id' => $document->connector_verification_requirement_id,
            'connector_verification_document_id' => $document->id,
            'actor_id' => $viewer->id,
            'action' => $action,
            'new_state' => [
                'status' => $document->status,
                'submission_round' => $document->submission_round,
            ],
            'submission_round' => $document->submission_round,
        ]);
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
                        new ConnectorApplicationSubmittedNotification($current, 'learner', 'resubmitted'),
                    );

                    try {
                        foreach ($this->adminRecipients() as $admin) {
                            $this->notifySafely(
                                $admin,
                                $current->id,
                                new ConnectorApplicationSubmittedNotification($current, 'admin', 'resubmitted'),
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

    public function sendExpirationWarnings(CarbonInterface $asOf): int
    {
        $warningCount = 0;
        $warningDays = array_values(array_unique(array_filter(
            array_map(static fn ($days): int => (int) $days, (array) config('connector_verification.expiration_warning_days', [30, 7])),
            static fn (int $days): bool => $days > 0,
        )));

        foreach ($warningDays as $days) {
            $warningDate = $asOf->copy()->addDays($days)->toDateString();

            ConnectorVerificationDocument::query()
                ->where('status', ConnectorVerificationDocument::STATUS_ACCEPTED)
                ->whereNull('superseded_at')
                ->whereDate('expiry_date', $warningDate)
                ->chunkById(100, function ($documents) use (&$warningCount, $days, $warningDate): void {
                    foreach ($documents as $document) {
                        if ($this->sendExpirationWarning($document, $days, $warningDate)) {
                            $warningCount++;
                        }
                    }
                });
        }

        return $warningCount;
    }

    public function expireDueDocuments(CarbonInterface $asOf): int
    {
        $expiredCount = 0;
        $asOfDate = $asOf->toDateString();

        ConnectorVerificationDocument::query()
            ->where('status', ConnectorVerificationDocument::STATUS_ACCEPTED)
            ->whereNull('superseded_at')
            ->whereDate('expiry_date', '<=', $asOfDate)
            ->chunkById(100, function ($documents) use (&$expiredCount, $asOfDate): void {
                foreach ($documents as $document) {
                    if ($this->expireDocument($document, $asOfDate)) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    private function sendExpirationWarning(
        ConnectorVerificationDocument $document,
        int $warningDays,
        string $warningDate,
    ): bool {
        $warned = false;

        DB::transaction(function () use (&$warned, $document, $warningDays, $warningDate): void {
            $connector = Connector::query()
                ->whereKey($document->connector_id)
                ->lockForUpdate()
                ->first();
            $requirement = ConnectorVerificationRequirement::query()
                ->whereKey($document->connector_verification_requirement_id)
                ->lockForUpdate()
                ->first();
            $lockedDocument = ConnectorVerificationDocument::query()
                ->whereKey($document->id)
                ->lockForUpdate()
                ->first();

            if (! $connector || ! $requirement || ! $lockedDocument
                || (int) $requirement->connector_id !== (int) $connector->id
                || (int) $lockedDocument->connector_id !== (int) $connector->id
                || (int) $lockedDocument->connector_verification_requirement_id !== (int) $requirement->id
                || in_array($connector->status, ['rejected', 'withdrawn'], true)
                || $lockedDocument->status !== ConnectorVerificationDocument::STATUS_ACCEPTED
                || $requirement->superseded_at !== null
                || $lockedDocument->superseded_at !== null
                || $lockedDocument->expiry_date === null
                || $lockedDocument->expiry_date->toDateString() !== $warningDate) {
                return;
            }

            $definition = $this->definitionFor($requirement);
            if (! ($definition['expires'] ?? false)) {
                return;
            }

            $action = 'document_expiration_warning_'.$warningDays;
            if ($connector->verificationAudits()
                ->where('connector_verification_document_id', $lockedDocument->id)
                ->where('action', $action)
                ->exists()) {
                return;
            }

            $connector->verificationAudits()->create([
                'connector_verification_requirement_id' => $requirement->id,
                'connector_verification_document_id' => $lockedDocument->id,
                'action' => $action,
                'new_state' => [
                    'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
                    'expiry_date' => $lockedDocument->expiry_date->toDateString(),
                    'warning_days' => $warningDays,
                ],
                'submission_round' => $lockedDocument->submission_round,
            ]);

            $requirementLabel = $requirement->label;
            DB::afterCommit(function () use ($connector, $requirementLabel): void {
                $current = $connector->fresh();
                $recipient = $current->primaryRepresentative()->first();

                if ($recipient) {
                    $this->notifySafely(
                        $recipient,
                        $current->id,
                        new ConnectorVerificationActionRequiredNotification(
                            $current,
                            'expiration_warning',
                            [$requirementLabel],
                        ),
                    );
                }
            });

            $warned = true;
        });

        return $warned;
    }

    private function expireDocument(
        ConnectorVerificationDocument $document,
        string $asOfDate,
    ): bool {
        $expired = false;

        DB::transaction(function () use (&$expired, $document, $asOfDate): void {
            $connector = Connector::query()
                ->whereKey($document->connector_id)
                ->lockForUpdate()
                ->first();
            $requirement = ConnectorVerificationRequirement::query()
                ->whereKey($document->connector_verification_requirement_id)
                ->lockForUpdate()
                ->first();
            $lockedDocument = ConnectorVerificationDocument::query()
                ->whereKey($document->id)
                ->lockForUpdate()
                ->first();

            if (! $connector || ! $requirement || ! $lockedDocument
                || (int) $requirement->connector_id !== (int) $connector->id
                || (int) $lockedDocument->connector_id !== (int) $connector->id
                || (int) $lockedDocument->connector_verification_requirement_id !== (int) $requirement->id
                || in_array($connector->status, ['rejected', 'withdrawn'], true)
                || $lockedDocument->status !== ConnectorVerificationDocument::STATUS_ACCEPTED
                || $requirement->superseded_at !== null
                || $lockedDocument->superseded_at !== null
                || $lockedDocument->expiry_date === null
                || $lockedDocument->expiry_date->toDateString() > $asOfDate) {
                return;
            }

            $definition = $this->definitionFor($requirement);
            if (! ($definition['expires'] ?? false)) {
                return;
            }

            $previousRequirementStatus = $requirement->status;
            $lockedDocument->update([
                'status' => ConnectorVerificationDocument::STATUS_EXPIRED,
            ]);

            $requirementStatus = $this->recomputeRequirementStatus($requirement);
            $requirement->update(['status' => $requirementStatus]);

            $connector->verificationAudits()->create([
                'connector_verification_requirement_id' => $requirement->id,
                'connector_verification_document_id' => $lockedDocument->id,
                'action' => 'document_expired',
                'previous_state' => [
                    'status' => ConnectorVerificationDocument::STATUS_ACCEPTED,
                    'requirement_status' => $previousRequirementStatus,
                ],
                'new_state' => [
                    'status' => ConnectorVerificationDocument::STATUS_EXPIRED,
                    'requirement_status' => $requirementStatus,
                    'expiry_date' => $lockedDocument->expiry_date->toDateString(),
                ],
                'submission_round' => $lockedDocument->submission_round,
            ]);

            $requirements = ConnectorVerificationRequirement::query()
                ->where('connector_id', $connector->id)
                ->whereNull('superseded_at')
                ->get();
            $verificationSatisfied = $this->verificationRulesSatisfied($requirements);
            $transitionAction = null;
            $previousVerificationStatus = $connector->verification_status;

            if (! $verificationSatisfied && $connector->status === 'verified'
                && $connector->verification_status !== Connector::VERIFICATION_REVERIFICATION_REQUIRED) {
                $connector->update([
                    'verification_status' => Connector::VERIFICATION_REVERIFICATION_REQUIRED,
                ]);
                $transitionAction = 'connector_reverification_required';
            } elseif (! $verificationSatisfied && $connector->status === 'pending'
                && $connector->verification_status !== Connector::VERIFICATION_RESUBMISSION_REQUIRED) {
                $connector->update([
                    'verification_status' => Connector::VERIFICATION_RESUBMISSION_REQUIRED,
                ]);
                $transitionAction = 'connector_resubmission_required';
            }

            if ($transitionAction !== null) {
                $connector->verificationAudits()->create([
                    'action' => $transitionAction,
                    'previous_state' => [
                        'verification_status' => $previousVerificationStatus,
                    ],
                    'new_state' => [
                        'verification_status' => $connector->verification_status,
                    ],
                    'submission_round' => (int) $connector->current_submission_round,
                ]);

                $requirementLabel = $requirement->label;
                DB::afterCommit(function () use ($connector, $requirementLabel): void {
                    $current = $connector->fresh();
                    $recipient = $current->primaryRepresentative()->first();

                    if ($recipient) {
                        $this->notifySafely(
                            $recipient,
                            $current->id,
                            new ConnectorVerificationActionRequiredNotification(
                                $current,
                                'reverification_required',
                                [$requirementLabel],
                            ),
                        );
                    }

                    try {
                        foreach ($this->adminRecipients() as $admin) {
                            $this->notifySafely(
                                $admin,
                                $current->id,
                                new ConnectorVerificationActionRequiredNotification(
                                    $current,
                                    'reverification_required',
                                    [$requirementLabel],
                                    'admin',
                                ),
                            );
                        }
                    } catch (Throwable $e) {
                        Log::warning('Connector expiration notification admin lookup failed.', [
                            'connector_id' => $current->id,
                            'exception' => $e,
                        ]);
                    }
                });
            }

            $expired = true;
        });

        return $expired;
    }

    private function recomputeRequirementStatus(ConnectorVerificationRequirement $requirement): string
    {
        $state = $this->requirementEvidenceState($requirement);

        if ($state['satisfied']) {
            return ConnectorVerificationRequirement::STATUS_SATISFIED;
        }

        $definition = $this->definitionFor($requirement);
        if ($requirement->status === ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE
            && ($definition['manual_not_applicable_allowed'] ?? false)) {
            return ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE;
        }

        if ($state['pending_count'] > 0) {
            return ConnectorVerificationRequirement::STATUS_PENDING;
        }

        if ($state['expired_count'] > 0) {
            return ConnectorVerificationRequirement::STATUS_EXPIRED;
        }

        return ConnectorVerificationRequirement::STATUS_REJECTED;
    }

    private function verificationRulesSatisfied($requirements): bool
    {
        $groups = [];

        foreach ($requirements as $requirement) {
            $state = $this->requirementEvidenceState($requirement);
            $groups[(string) $requirement->rule_group][] = [
                'mode' => (string) $requirement->rule_mode,
                'required' => (bool) $requirement->is_required,
                'satisfied' => $state['satisfied'],
                'accepted_count' => $state['accepted_count'],
                'minimum' => max(1, (int) $requirement->minimum_evidence_count),
            ];
        }

        foreach ($groups as $items) {
            $mode = $items[0]['mode'] ?? 'all';

            if ($mode === 'any' && ! collect($items)->contains('satisfied', true)) {
                return false;
            }

            if ($mode === 'minimum') {
                $minimum = max(array_column($items, 'minimum'));
                if (array_sum(array_column($items, 'accepted_count')) < $minimum) {
                    return false;
                }
            }

            if ($mode === 'all' && collect($items)->contains(
                fn (array $item): bool => $item['required'] && ! $item['satisfied'],
            )) {
                return false;
            }
        }

        return true;
    }

    private function requirementEvidenceState(ConnectorVerificationRequirement $requirement): array
    {
        $documents = $requirement->documents()
            ->whereNull('superseded_at')
            ->get(['status']);
        $acceptedCount = $documents
            ->where('status', ConnectorVerificationDocument::STATUS_ACCEPTED)
            ->count();
        $pendingCount = $documents
            ->where('status', ConnectorVerificationDocument::STATUS_PENDING)
            ->count();
        $expiredCount = $documents
            ->where('status', ConnectorVerificationDocument::STATUS_EXPIRED)
            ->count();

        $identityValid = $requirement->learner_identity_verification_id !== null
            && LearnerIdentityVerification::query()
                ->whereKey($requirement->learner_identity_verification_id)
                ->where('status', 'approved')
                ->whereNull('superseded_at')
                ->exists();

        $definition = $this->definitionFor($requirement);
        $manualNotApplicable = $requirement->status === ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE
            && ($definition['manual_not_applicable_allowed'] ?? false);

        return [
            'accepted_count' => $acceptedCount,
            'pending_count' => $pendingCount,
            'expired_count' => $expiredCount,
            'satisfied' => $identityValid
                || $acceptedCount >= max(1, (int) $requirement->minimum_evidence_count)
                || $manualNotApplicable,
        ];
    }

    private function assertDecisionRound(Connector $connector, int $expectedRound): void
    {
        if ((int) $connector->current_submission_round !== $expectedRound) {
            throw new ConflictHttpException('This verification review is stale. Refresh the admin workspace.');
        }
    }

    private function assertApprovalEvidence(
        Connector $connector,
        $requirements,
        int $expectedRound,
    ): void {
        $groups = [];

        foreach ($requirements as $requirement) {
            $definition = $this->definitionFor($requirement);
            $documents = $requirement->documents()
                ->whereNull('superseded_at')
                ->where('submission_round', $expectedRound)
                ->get();
            $acceptedCount = $documents
                ->where('status', ConnectorVerificationDocument::STATUS_ACCEPTED)
                ->count();
            $hasBlockedRequiredDocument = $documents->contains(
                fn (ConnectorVerificationDocument $document): bool => in_array($document->status, [
                    ConnectorVerificationDocument::STATUS_PENDING,
                    ConnectorVerificationDocument::STATUS_REJECTED,
                    ConnectorVerificationDocument::STATUS_EXPIRED,
                ], true),
            );

            $identityValid = false;
            if ($requirement->learner_identity_verification_id !== null) {
                $identityValid = LearnerIdentityVerification::query()
                    ->whereKey($requirement->learner_identity_verification_id)
                    ->where('status', 'approved')
                    ->whereNull('superseded_at')
                    ->exists();

                if (! $identityValid) {
                    throw new HttpException(422, 'The referenced representative identity verification is not currently approved.');
                }
            }

            $notApplicableAudited = $requirement->status === ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE
                && ($definition['manual_not_applicable_allowed'] ?? false)
                && $connector->verificationAudits()
                    ->where('connector_verification_requirement_id', $requirement->id)
                    ->where('action', 'requirement_not_applicable')
                    ->where('submission_round', $expectedRound)
                    ->exists();

            $satisfied = $identityValid
                || $acceptedCount >= max(1, (int) $requirement->minimum_evidence_count)
                || $notApplicableAudited;

            if ($requirement->is_required && ! $satisfied) {
                throw new HttpException(422, 'Required connector verification evidence is incomplete.');
            }

            if ($requirement->is_required && $hasBlockedRequiredDocument) {
                throw new HttpException(422, 'A required connector verification document is still pending or rejected.');
            }

            $group = (string) $requirement->rule_group;
            $groups[$group][] = [
                'mode' => (string) $requirement->rule_mode,
                'satisfied' => $satisfied,
                'accepted_count' => $acceptedCount,
                'minimum' => max(1, (int) $requirement->minimum_evidence_count),
            ];
        }

        foreach ($groups as $items) {
            $mode = $items[0]['mode'] ?? 'all';

            if ($mode === 'any' && ! collect($items)->contains('satisfied', true)) {
                throw new HttpException(422, 'At least one requirement in an alternative evidence group must be satisfied.');
            }

            if ($mode === 'minimum') {
                $minimum = max(array_column($items, 'minimum'));
                $accepted = array_sum(array_column($items, 'accepted_count'));

                if ($accepted < $minimum) {
                    throw new HttpException(422, 'The minimum evidence threshold has not been met.');
                }
            }
        }

        $authority = $requirements->firstWhere('requirement_key', 'representative_authority');
        if (! $authority) {
            throw new HttpException(422, 'Representative authority is required for approval.');
        }

        $authorityDocuments = $authority->documents()
            ->whereNull('superseded_at')
            ->where('submission_round', $expectedRound)
            ->get();
        $authorityAccepted = $authorityDocuments
            ->where('status', ConnectorVerificationDocument::STATUS_ACCEPTED)
            ->count();
        $authorityIdentityValid = $authority->learner_identity_verification_id !== null
            && LearnerIdentityVerification::query()
                ->whereKey($authority->learner_identity_verification_id)
                ->where('status', 'approved')
                ->whereNull('superseded_at')
                ->exists();
        $authoritySatisfied = $authorityIdentityValid
            || $authorityAccepted >= max(1, (int) $authority->minimum_evidence_count)
            || (
                $authority->status === ConnectorVerificationRequirement::STATUS_NOT_APPLICABLE
                && ($this->definitionFor($authority)['manual_not_applicable_allowed'] ?? false)
            );

        if (! $authoritySatisfied) {
            throw new HttpException(422, 'Representative authority must be independently satisfied.');
        }
    }

    private function cleanNote(?string $note): ?string
    {
        $clean = trim((string) $note);

        return $clean === '' ? null : $clean;
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

    private function notifySafely(User $user, int $connectorId, BaseNotification $notification): void
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
