<?php

namespace App\Services;

use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\Admin\RelationshipVerificationSubmittedNotification;
use App\Notifications\RelationshipVerificationStatusNotification;
use App\Services\Chat\GuardianInvitationConversationService;
use App\Support\GuardianRelationshipTypes;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class GuardianRelationshipVerificationService
{
    public function __construct(
        private readonly GuardianRelationshipEvidenceService $evidence,
        private readonly GuardianInvitationConversationService $invitationConversations,
    ) {}

    public function initialStatus(string $relationshipType): string
    {
        return GuardianRelationshipTypes::initialVerificationStatus($relationshipType);
    }

    public function submit(
        ParentChildAccount $relationship,
        User $guardian,
        array $documents,
        ?string $circumstances,
        ?array &$storedPaths = null,
    ): ParentChildAccount {
        $storedPaths = [];

        try {
            $submitted = DB::transaction(function () use ($relationship, $guardian, $documents, $circumstances, &$storedPaths): ParentChildAccount {
                $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
                $this->assertGuardianOwns($locked, $guardian);
                $this->assertState($locked, [
                    ParentChildAccount::VERIFICATION_PENDING,
                    ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
                ]);

                if (! $locked->verificationAudits()->where('action', 'claim_created')->exists()) {
                    $this->audit($locked, $guardian, 'claim_created', null, ParentChildAccount::VERIFICATION_PENDING);
                }

                $round = ((int) $locked->current_evidence_round) + 1;
                if ($locked->current_evidence_round > 0) {
                    $locked->verificationDocuments()
                        ->where('submission_round', $locked->current_evidence_round)
                        ->whereNull('superseded_at')
                        ->update(['superseded_at' => now()]);
                }
                $this->evidence->storeUploadedRound($locked, $guardian, $round, $documents, $storedPaths);

                $previous = (string) $locked->relationship_verified_status;
                $locked->update([
                    'verification_pathway' => $locked->verification_pathway ?: GuardianRelationshipTypes::pathway($locked->relationship_type),
                    'relationship_status' => ParentChildAccount::STATUS_PENDING,
                    'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                    'current_evidence_round' => $round,
                    'relationship_notes' => $circumstances,
                    'relationship_verification_submitted_at' => now(),
                    'relationship_verification_reviewed_by' => null,
                    'relationship_verification_reviewed_at' => null,
                    'relationship_verification_rejection_reason' => null,
                    'relationship_verification_rejection_note' => null,
                    'relationship_verification_revoked_at' => null,
                    'relationship_deactivated_at' => null,
                ]);

                $action = $previous === ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED ? 'resubmitted' : 'submitted';
                $this->audit($locked, $guardian, $action, $previous, ParentChildAccount::VERIFICATION_UNDER_REVIEW, $round);
                $this->notifyAfterCommit($locked->id, $action, true);

                return $locked->fresh(['parent', 'child', 'verificationDocuments']);
            });
            $this->syncInvitationConversationsAfterCommit((int) $submitted->id);

            return $submitted;
        } catch (Throwable $exception) {
            $this->evidence->deleteStoredPaths($storedPaths);
            throw $exception;
        }
    }

    public function submitStaged(ParentChildAccount $relationship, User $guardian, array $documents, ?Closure $onSubmitted = null, ?array &$movedDocuments = null): ParentChildAccount
    {
        $movedDocuments = [];

        try {
            $submitted = DB::transaction(function () use ($relationship, $guardian, $documents, $onSubmitted, &$movedDocuments): ParentChildAccount {
                $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
                $this->assertGuardianOwns($locked, $guardian);
                $this->assertState($locked, [
                    ParentChildAccount::VERIFICATION_PENDING,
                    ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
                ]);

                if (! $locked->verificationAudits()->where('action', 'claim_created')->exists()) {
                    $this->audit($locked, $guardian, 'claim_created', null, ParentChildAccount::VERIFICATION_PENDING);
                }

                $round = ((int) $locked->current_evidence_round) + 1;
                if ($locked->current_evidence_round > 0) {
                    $locked->verificationDocuments()
                        ->where('submission_round', $locked->current_evidence_round)
                        ->whereNull('superseded_at')
                        ->update(['superseded_at' => now()]);
                }

                $this->evidence->storeStagedRound($locked, $guardian, $round, $documents, $movedDocuments);

                $previous = (string) $locked->relationship_verified_status;
                $locked->update([
                    'verification_pathway' => $locked->verification_pathway ?: GuardianRelationshipTypes::pathway($locked->relationship_type),
                    'relationship_status' => ParentChildAccount::STATUS_PENDING,
                    'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                    'current_evidence_round' => $round,
                    'relationship_verification_submitted_at' => now(),
                    'relationship_verification_reviewed_by' => null,
                    'relationship_verification_reviewed_at' => null,
                    'relationship_verification_rejection_reason' => null,
                    'relationship_verification_rejection_note' => null,
                    'relationship_verification_revoked_at' => null,
                    'relationship_deactivated_at' => null,
                    'relationship_verified_at' => null,
                ]);

                $action = $previous === ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED ? 'resubmitted' : 'submitted';
                $this->audit($locked, $guardian, $action, $previous, ParentChildAccount::VERIFICATION_UNDER_REVIEW, $round);
                $onSubmitted?->__invoke($locked);
                $this->notifyAfterCommit($locked->id, $action, true);

                return $locked->fresh(['parent', 'child', 'verificationDocuments']);
            });
            $this->syncInvitationConversationsAfterCommit((int) $submitted->id);

            return $submitted;
        } catch (Throwable $exception) {
            $this->evidence->restoreStagedDocuments($movedDocuments);

            throw $exception;
        }
    }

    public function submitDeclaration(ParentChildAccount $relationship, User $guardian, ?Closure $onSubmitted = null): ParentChildAccount
    {
        if ($relationship->requiresRelationshipVerification()) {
            throw new InvalidArgumentException('Relationship evidence is required before submission.');
        }

        $submitted = DB::transaction(function () use ($relationship, $guardian, $onSubmitted): ParentChildAccount {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
            $this->assertGuardianOwns($locked, $guardian);

            $previous = (string) ($locked->relationship_verified_status ?: ParentChildAccount::VERIFICATION_PENDING);
            $locked->update([
                'verification_pathway' => GuardianRelationshipTypes::pathway($locked->relationship_type),
                'relationship_status' => ParentChildAccount::STATUS_PENDING,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                'relationship_verification_submitted_at' => now(),
                'relationship_verification_reviewed_by' => null,
                'relationship_verification_reviewed_at' => null,
                'relationship_verification_rejection_reason' => null,
                'relationship_verification_rejection_note' => null,
                'relationship_verification_revoked_at' => null,
                'relationship_verified_at' => null,
            ]);
            $this->audit($locked, $guardian, 'submitted', $previous, ParentChildAccount::VERIFICATION_UNDER_REVIEW, 0);
            $onSubmitted?->__invoke($locked);
            $this->notifyAfterCommit($locked->id, 'submitted', true);

            return $locked->fresh(['parent', 'child']);
        });
        $this->syncInvitationConversationsAfterCommit((int) $submitted->id);

        return $submitted;
    }

    public function approve(ParentChildAccount $relationship, User $admin): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, 'approved', [ParentChildAccount::VERIFICATION_UNDER_REVIEW], function (ParentChildAccount $locked) use ($admin): array {
            if ($locked->current_evidence_round < 1 || ! $locked->verificationDocuments()
                ->where('submission_round', $locked->current_evidence_round)
                ->whereNull('superseded_at')
                ->exists()) {
                throw new InvalidArgumentException('Current-round evidence is required before approval.');
            }

            return [
                'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
                'relationship_verification_reviewed_by' => $admin->id,
                'relationship_verification_reviewed_at' => now(),
                'relationship_verification_rejection_reason' => null,
                'relationship_verification_rejection_note' => null,
                'relationship_verification_revoked_at' => null,
                'relationship_deactivated_at' => null,
                'relationship_verified_at' => now(),
            ];
        });
    }

    public function reject(ParentChildAccount $relationship, User $admin, string $reasonCode, ?string $note, bool $allowResubmission = true): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, $allowResubmission ? 'resubmission_required' : 'rejected', [ParentChildAccount::VERIFICATION_UNDER_REVIEW], fn (): array => [
            'relationship_status' => $allowResubmission ? ParentChildAccount::STATUS_PENDING : ParentChildAccount::STATUS_REJECTED,
            'relationship_verified_status' => $allowResubmission ? ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED : ParentChildAccount::VERIFICATION_REJECTED,
            'relationship_verification_reviewed_by' => $admin->id,
            'relationship_verification_reviewed_at' => now(),
            'relationship_verification_rejection_reason' => $reasonCode,
            'relationship_verification_rejection_note' => $note,
            'relationship_verified_at' => null,
        ], $reasonCode, $note);
    }

    public function closePendingClaim(ParentChildAccount $relationship, User $admin, ?string $note): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, 'claim_closed', [ParentChildAccount::VERIFICATION_PENDING, ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED], fn (): array => [
            'relationship_status' => ParentChildAccount::STATUS_REJECTED,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_REJECTED,
            'relationship_verification_reviewed_by' => $admin->id,
            'relationship_verification_reviewed_at' => now(),
            'relationship_verification_rejection_reason' => 'claim_closed',
            'relationship_verification_rejection_note' => $note,
            'relationship_verified_at' => null,
        ], 'claim_closed', $note);
    }

    public function revoke(ParentChildAccount $relationship, User $admin, string $reasonCode, ?string $note): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, 'revoked', [ParentChildAccount::VERIFICATION_VERIFIED], function (ParentChildAccount $locked) use ($admin, $reasonCode, $note): array {
            if (! $locked->isVerifiedActive()) {
                throw new InvalidArgumentException('Only an active verified relationship can be revoked.');
            }

            return [
                'relationship_status' => ParentChildAccount::STATUS_REVOKED,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED,
                'relationship_verification_reviewed_by' => $admin->id,
                'relationship_verification_reviewed_at' => now(),
                'relationship_verification_rejection_reason' => $reasonCode,
                'relationship_verification_rejection_note' => $note,
                'relationship_verification_revoked_at' => now(),
            ];
        }, $reasonCode, $note);
    }

    private function decide(ParentChildAccount $relationship, User $actor, string $action, array $states, Closure $updates, ?string $reasonCode = null, ?string $note = null): ParentChildAccount
    {
        $updated = DB::transaction(function () use ($relationship, $actor, $action, $states, $updates, $reasonCode, $note): ParentChildAccount {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
            $this->assertState($locked, $states);
            $previous = (string) $locked->relationship_verified_status;
            $locked->update($updates($locked));
            $this->audit($locked, $actor, $action, $previous, $locked->relationship_verified_status, (int) $locked->current_evidence_round, $reasonCode, $note);
            $this->notifyAfterCommit($locked->id, $action);

            return $locked->fresh(['parent', 'child', 'verificationDocuments']);
        });
        $this->syncInvitationConversationsAfterCommit((int) $updated->id);

        return $updated;
    }

    public function deactivate(ParentChildAccount $relationship, User $actor, ?string $note): ParentChildAccount
    {
        $updated = DB::transaction(function () use ($relationship, $actor, $note): ParentChildAccount {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
            $this->assertPartyOrAdmin($locked, $actor);

            if (! $locked->isVerifiedActive()) {
                throw new InvalidArgumentException('Only an active verified relationship can be deactivated.');
            }

            $locked->update([
                'relationship_status' => ParentChildAccount::STATUS_INACTIVE,
                'relationship_deactivated_at' => now(),
            ]);
            $this->audit($locked, $actor, 'deactivated', ParentChildAccount::VERIFICATION_VERIFIED, ParentChildAccount::VERIFICATION_VERIFIED, (int) $locked->current_evidence_round, null, $note);
            $this->notifyAfterCommit($locked->id, 'deactivated');

            return $locked->fresh(['parent', 'child']);
        });
        $this->syncInvitationConversationsAfterCommit((int) $updated->id);

        return $updated;
    }

    public function requestReactivation(ParentChildAccount $relationship, User $actor): ParentChildAccount
    {
        $updated = DB::transaction(function () use ($relationship, $actor): ParentChildAccount {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
            $this->assertPartyOrAdmin($locked, $actor);

            if ($locked->relationship_status !== ParentChildAccount::STATUS_INACTIVE
                || $locked->relationship_verified_status !== ParentChildAccount::VERIFICATION_VERIFIED) {
                throw new InvalidArgumentException('Only an inactive previously verified relationship can request reactivation.');
            }

            $locked->update([
                'relationship_status' => ParentChildAccount::STATUS_PENDING,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                'relationship_verification_submitted_at' => now(),
                'relationship_verification_reviewed_by' => null,
                'relationship_verification_reviewed_at' => null,
                'relationship_verification_rejection_reason' => null,
                'relationship_verification_rejection_note' => null,
                'relationship_verification_revoked_at' => null,
                'relationship_deactivated_at' => null,
                'relationship_verified_at' => null,
            ]);
            $this->audit($locked, $actor, 'reactivation_requested', ParentChildAccount::VERIFICATION_VERIFIED, ParentChildAccount::VERIFICATION_UNDER_REVIEW, (int) $locked->current_evidence_round);
            $this->notifyAfterCommit($locked->id, 'reactivation_requested', true);

            return $locked->fresh(['parent', 'child']);
        });
        $this->syncInvitationConversationsAfterCommit((int) $updated->id);

        return $updated;
    }

    private function syncInvitationConversationsAfterCommit(int $relationshipId): void
    {
        DB::afterCommit(fn () => $this->invitationConversations->syncForRelationship($relationshipId));
    }

    private function assertAdministrator(User $actor): void
    {
        if ($actor->status !== User::STATUS_ACTIVE || ! $actor->hasRole('admin')) {
            throw new AuthorizationException;
        }
    }

    private function assertGuardianOwns(ParentChildAccount $relationship, User $guardian): void
    {
        if ((int) $relationship->parent_user_id !== (int) $guardian->id
            || $guardian->status !== User::STATUS_ACTIVE
            || $guardian->parent_verification_status !== 'approved') {
            throw new AuthorizationException;
        }
    }

    private function assertPartyOrAdmin(ParentChildAccount $relationship, User $actor): void
    {
        if ($actor->status !== User::STATUS_ACTIVE
            || (! $actor->hasRole('admin') && ! in_array((int) $actor->id, [(int) $relationship->parent_user_id, (int) $relationship->child_user_id], true))) {
            throw new AuthorizationException;
        }
    }

    private function assertState(ParentChildAccount $relationship, array $states): void
    {
        if (! in_array($relationship->relationship_verified_status, $states, true)) {
            throw new InvalidArgumentException('This relationship cannot transition from its current verification state.');
        }
    }

    private function audit(
        ParentChildAccount $relationship,
        User $actor,
        string $action,
        ?string $previous,
        ?string $new,
        ?int $submissionRound = null,
        ?string $reasonCode = null,
        ?string $notes = null,
    ): void {
        GuardianRelationshipVerificationAudit::query()->create([
            'parent_child_account_id' => $relationship->id,
            'actor_user_id' => $actor->id,
            'action' => $action,
            'previous_status' => $previous,
            'new_status' => $new,
            'submission_round' => $submissionRound,
            'reason_code' => $reasonCode,
            'notes' => $notes,
        ]);
    }

    private function notifyAfterCommit(int $relationshipId, string $action, bool $notifyAdmins = false): void
    {
        DB::afterCommit(function () use ($relationshipId, $action, $notifyAdmins): void {
            try {
                $relationship = ParentChildAccount::with(['parent', 'child'])->find($relationshipId);
                if ($relationship === null) {
                    return;
                }

                foreach ([$relationship->parent, $relationship->child] as $target) {
                    if ($target === null) {
                        continue;
                    }

                    try {
                        $target->notify(new RelationshipVerificationStatusNotification($relationship, $action));
                    } catch (Throwable $exception) {
                        Log::warning('Relationship verification notification failed.', [
                            'relationship_id' => $relationshipId,
                            'action' => $action,
                            'target_user_id' => $target->id,
                            'exception_class' => $exception::class,
                        ]);
                    }
                }

                if ($notifyAdmins) {
                    foreach (User::role('admin')->where('status', User::STATUS_ACTIVE)->get() as $admin) {
                        try {
                            $admin->notify(new RelationshipVerificationSubmittedNotification($relationship));
                        } catch (Throwable $exception) {
                            Log::warning('Relationship verification notification failed.', [
                                'relationship_id' => $relationshipId,
                                'action' => $action,
                                'target_user_id' => $admin->id,
                                'exception_class' => $exception::class,
                            ]);
                        }
                    }
                }
            } catch (Throwable $exception) {
                Log::warning('Relationship verification notification lookup failed.', [
                    'relationship_id' => $relationshipId,
                    'action' => $action,
                    'target_user_id' => null,
                    'exception_class' => $exception::class,
                ]);
            }
        });
    }
}
