<?php

namespace App\Services;

use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\Admin\RelationshipVerificationSubmittedNotification;
use App\Notifications\RelationshipVerificationStatusNotification;
use App\Services\Chat\GuardianInvitationConversationService;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class GuardianRelationshipVerificationService
{
    public function __construct(
        private readonly GuardianRelationshipEvidenceService $evidence,
        private readonly GuardianInvitationConversationService $invitationConversations,
        private readonly DependentSupportInformationService $supportInformation,
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
        ?int $currentEvidenceRound = null,
    ): ParentChildAccount {
        $storedPaths = [];

        try {
            $submitted = DB::transaction(function () use ($relationship, $guardian, $documents, $circumstances, &$storedPaths, $currentEvidenceRound): ParentChildAccount {
                $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
                $this->assertGuardianOwns($locked, $guardian);
                $this->assertState($locked, [
                    ParentChildAccount::VERIFICATION_PENDING,
                    ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
                ]);

                $retained = $this->correctionCarryForward($locked, $documents, $currentEvidenceRound);

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
                $uploaded = $this->evidence->storeUploadedRound($locked, $guardian, $round, $documents, $storedPaths);
                $this->carryForwardDocuments($retained, $uploaded, $round);

                $previous = (string) $locked->relationship_verified_status;
                $locked->update([
                    'verification_pathway' => $locked->verification_pathway ?: GuardianRelationshipTypes::pathway($locked->relationship_type),
                    'relationship_status' => ParentChildAccount::STATUS_PENDING,
                    'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                    'current_evidence_round' => $round,
                    'relationship_correction_request' => null,
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
        } catch (Throwable $exception) {
            $this->evidence->deleteStoredPaths($storedPaths);
            throw $exception;
        }

        $this->syncInvitationConversationsAfterCommit((int) $submitted->id);

        return $submitted;
    }

    public function submitStaged(ParentChildAccount $relationship, User $guardian, array $documents, ?Closure $onSubmitted = null, ?array &$movedDocuments = null, ?int $currentEvidenceRound = null): ParentChildAccount
    {
        $movedDocuments = [];

        try {
            $submitted = DB::transaction(function () use ($relationship, $guardian, $documents, $onSubmitted, &$movedDocuments, $currentEvidenceRound): ParentChildAccount {
                $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
                $this->assertGuardianOwns($locked, $guardian);
                $this->assertState($locked, [
                    ParentChildAccount::VERIFICATION_PENDING,
                    ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
                ]);

                $retained = $this->correctionCarryForward($locked, $documents, $currentEvidenceRound);

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

                $uploaded = $this->evidence->storeStagedRound($locked, $guardian, $round, $documents, $movedDocuments);
                $this->carryForwardDocuments($retained, $uploaded, $round);

                $previous = (string) $locked->relationship_verified_status;
                $locked->update([
                    'verification_pathway' => $locked->verification_pathway ?: GuardianRelationshipTypes::pathway($locked->relationship_type),
                    'relationship_status' => ParentChildAccount::STATUS_PENDING,
                    'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                    'current_evidence_round' => $round,
                    'relationship_correction_request' => null,
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
        } catch (Throwable $exception) {
            $this->evidence->restoreStagedDocuments($movedDocuments);

            throw $exception;
        }

        $this->syncInvitationConversationsAfterCommit((int) $submitted->id);

        return $submitted;
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

    public function approve(ParentChildAccount $relationship, User $admin, ?int $currentEvidenceRound = null): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        $currentEvidenceRound ??= (int) $relationship->current_evidence_round;

        return $this->decide($relationship, $admin, 'approved', [ParentChildAccount::VERIFICATION_UNDER_REVIEW], function (ParentChildAccount $locked) use ($admin, $currentEvidenceRound): array {
            abort_unless($currentEvidenceRound === (int) $locked->current_evidence_round, 409, 'A newer evidence round is available. Reload before reviewing.');
            if ($locked->current_evidence_round < 1 || ! $locked->verificationDocuments()
                ->where('submission_round', $locked->current_evidence_round)
                ->whereNull('superseded_at')
                ->exists()) {
                throw new InvalidArgumentException('Current-round evidence is required before approval.');
            }

            $locked->verificationDocuments()->where('submission_round', $locked->current_evidence_round)
                ->whereNull('superseded_at')->update(['review_status' => 'accepted']);

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

    public function reject(
        ParentChildAccount $relationship,
        User $admin,
        string $reasonCode,
        ?string $note,
        bool $allowResubmission = true,
        ?array $requestedDocumentIds = null,
        ?array $requestedDocumentTypes = null,
        ?int $currentEvidenceRound = null,
    ): ParentChildAccount {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, $allowResubmission ? 'resubmission_required' : 'rejected', [ParentChildAccount::VERIFICATION_UNDER_REVIEW], function (ParentChildAccount $locked) use ($admin, $reasonCode, $note, $allowResubmission, $requestedDocumentIds, $requestedDocumentTypes, $currentEvidenceRound): array {
            $request = null;
            if ($allowResubmission && ($requestedDocumentIds !== null || $requestedDocumentTypes !== null)) {
                $request = $this->correctionRequest($locked, $requestedDocumentIds ?? [], $requestedDocumentTypes ?? [], $currentEvidenceRound);
                Validator::make(['note' => $note], ['note' => ['required', 'string', 'max:1000']])->validate();
            } elseif ($currentEvidenceRound !== null && $currentEvidenceRound !== (int) $locked->current_evidence_round) {
                throw ValidationException::withMessages(['current_evidence_round' => 'This review is out of date. Reload the current evidence.']);
            }

            $currentDocuments = $locked->verificationDocuments()->where('submission_round', $locked->current_evidence_round)->whereNull('superseded_at')->get();
            foreach ($currentDocuments as $document) {
                $document->update(['review_status' => ! $allowResubmission ? 'rejected'
                    : ($request === null || in_array($document->id, $request['document_ids'], true) ? 'changes_requested' : 'accepted')]);
            }
            $this->supportInformation->resetGuardianAccessForLifecycle($locked, $admin);

            return [
                'relationship_status' => $allowResubmission ? ParentChildAccount::STATUS_PENDING : ParentChildAccount::STATUS_REJECTED,
                'relationship_verified_status' => $allowResubmission ? ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED : ParentChildAccount::VERIFICATION_REJECTED,
                'relationship_verification_reviewed_by' => $admin->id,
                'relationship_verification_reviewed_at' => now(),
                'relationship_verification_rejection_reason' => $reasonCode,
                'relationship_verification_rejection_note' => $note,
                'relationship_correction_request' => $request,
                'relationship_verified_at' => null,
            ];
        }, $reasonCode, $note);
    }

    public function closePendingClaim(ParentChildAccount $relationship, User $admin, ?string $note): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, 'claim_closed', [ParentChildAccount::VERIFICATION_PENDING, ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED], function (ParentChildAccount $locked) use ($admin, $note): array {
            $this->supportInformation->resetGuardianAccessForLifecycle($locked, $admin);

            return [
                'relationship_status' => ParentChildAccount::STATUS_REJECTED,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_REJECTED,
                'relationship_verification_reviewed_by' => $admin->id,
                'relationship_verification_reviewed_at' => now(),
                'relationship_verification_rejection_reason' => 'claim_closed',
                'relationship_verification_rejection_note' => $note,
                'relationship_verified_at' => null,
            ];
        }, 'claim_closed', $note);
    }

    public function revoke(ParentChildAccount $relationship, User $admin, string $reasonCode, ?string $note): ParentChildAccount
    {
        $this->assertAdministrator($admin);

        return $this->decide($relationship, $admin, 'revoked', [ParentChildAccount::VERIFICATION_VERIFIED], function (ParentChildAccount $locked) use ($admin, $reasonCode, $note): array {
            if (! $locked->isVerifiedActive()) {
                throw new InvalidArgumentException('Only an active verified relationship can be revoked.');
            }

            $this->supportInformation->resetGuardianAccessForLifecycle($locked, $admin);

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

            $this->supportInformation->resetGuardianAccessForLifecycle($locked, $actor);

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
                'can_manage_support_information' => false,
            ]);
            $this->audit($locked, $actor, 'reactivation_requested', ParentChildAccount::VERIFICATION_VERIFIED, ParentChildAccount::VERIFICATION_UNDER_REVIEW, (int) $locked->current_evidence_round);
            $this->notifyAfterCommit($locked->id, 'reactivation_requested', true);

            return $locked->fresh(['parent', 'child']);
        });
        $this->syncInvitationConversationsAfterCommit((int) $updated->id);

        return $updated;
    }

    private function correctionRequest(ParentChildAccount $relationship, array $ids, array $types, ?int $round): array
    {
        Validator::make([
            'current_evidence_round' => $round,
            'requested_document_ids' => $ids,
            'requested_document_types' => $types,
        ], [
            'current_evidence_round' => ['required', 'integer', Rule::in([$relationship->current_evidence_round])],
            'requested_document_ids' => ['array', 'max:10'],
            'requested_document_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'requested_document_types' => ['array', 'max:10'],
            'requested_document_types.*' => ['required', 'string', 'distinct', Rule::in(GuardianRelationshipTypes::acceptedDocumentTypes($relationship->relationship_type))],
        ])->validate();

        $ids = array_map('intval', $ids);
        $documents = $relationship->verificationDocuments()->where('submission_round', $relationship->current_evidence_round)
            ->whereNull('superseded_at')->get();
        if ($documents->isEmpty() || ($ids === [] && $types === [])
            || array_diff($ids, $documents->modelKeys()) !== []) {
            throw ValidationException::withMessages(['requested_document_ids' => 'Select current evidence to replace or a missing document type.']);
        }
        if (array_intersect($types, $documents->pluck('document_type')->all()) !== []) {
            throw ValidationException::withMessages(['requested_document_types' => 'Select existing evidence for replacement when that document type is already present.']);
        }
        if ($documents->count() + count($types) > 10) {
            throw ValidationException::withMessages(['requested_document_types' => 'The corrected evidence round may contain at most 10 files.']);
        }

        // A paired document is corrected as a unit, preserving the front/back contract.
        $pairKeys = $documents->whereIn('id', $ids)->pluck('pairing_key')->filter()->all();
        $ids = $documents->filter(fn ($document) => in_array((int) $document->id, $ids, true)
            || ($document->pairing_key && in_array($document->pairing_key, $pairKeys, true)))
            ->modelKeys();

        return ['round' => (int) $round, 'document_ids' => array_values($ids), 'document_types' => array_values($types)];
    }

    private function correctionCarryForward(ParentChildAccount $relationship, array $documents, ?int $round): Collection
    {
        if (in_array($relationship->relationship_status, [ParentChildAccount::STATUS_REJECTED, ParentChildAccount::STATUS_REVOKED, ParentChildAccount::STATUS_INACTIVE], true)) {
            throw ValidationException::withMessages(['documents' => 'This relationship claim is closed for submissions.']);
        }
        $request = $relationship->relationship_correction_request;
        if (($round !== null && $round !== (int) $relationship->current_evidence_round)
            || ($request !== null && ($round === null || $round !== (int) $request['round']))) {
            throw ValidationException::withMessages(['current_evidence_round' => 'This form is out of date. Reload the current evidence.']);
        }

        Validator::make(['documents' => $documents], [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*' => ['required', 'array'],
            'documents.*.document_type' => ['required', 'string', Rule::in(GuardianRelationshipTypes::acceptedDocumentTypes($relationship->relationship_type))],
            'documents.*.document_side' => ['required', 'string', Rule::in(['front', 'back', 'not_applicable'])],
            'documents.*.pairing_key' => ['nullable', 'uuid'],
            'documents.*.replacement_document_id' => ['nullable', 'integer', 'min:1', 'distinct'],
        ])->validate();

        $retained = collect();
        if ($request !== null) {
            if ($relationship->relationship_verified_status !== ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED) {
                throw ValidationException::withMessages(['documents' => 'Corrections are not open for this relationship.']);
            }
            $current = $relationship->verificationDocuments()->where('submission_round', $relationship->current_evidence_round)
                ->whereNull('superseded_at')->get()->keyBy('id');
            $replaced = [];
            $addedTypes = [];
            foreach ($documents as $item) {
                $id = isset($item['replacement_document_id']) ? (int) $item['replacement_document_id'] : null;
                if ($id !== null) {
                    $original = $current->get($id);
                    if (! in_array($id, $request['document_ids'], true) || $original === null
                        || ($item['document_type'] ?? null) !== $original->document_type
                        || ($item['document_side'] ?? null) !== $original->document_side
                        || ($item['pairing_key'] ?? null) !== $original->pairing_key) {
                        throw ValidationException::withMessages(['documents' => 'Replace only the requested current documents with their matching type and side.']);
                    }
                    $replaced[] = $id;
                } elseif (! in_array($item['document_type'] ?? null, $request['document_types'], true)) {
                    throw ValidationException::withMessages(['documents' => 'Upload only the requested missing document types.']);
                } else {
                    $addedTypes[] = $item['document_type'];
                }
            }
            if (array_diff($request['document_ids'], $replaced) !== [] || array_diff($request['document_types'], $addedTypes) !== []) {
                throw ValidationException::withMessages(['documents' => 'Provide every requested replacement and missing document before submitting.']);
            }
            $retained = $current->except($request['document_ids'])->values();
        } elseif (collect($documents)->contains(fn (array $item) => filled($item['replacement_document_id'] ?? null))) {
            throw ValidationException::withMessages(['documents' => 'This relationship has no targeted document replacement request.']);
        }

        $effective = array_merge($retained->map->toArray()->all(), array_values($documents));
        $errors = GuardianRelationshipEvidenceRules::metadataErrors($effective);
        if (count($effective) > 10) {
            $errors['documents'] = 'The corrected evidence round may contain at most 10 files.';
        }
        if (array_intersect(GuardianRelationshipTypes::requiredDocumentTypes($relationship->relationship_type), array_column($effective, 'document_type')) === []) {
            $errors['documents'] = 'At least one core document for this verification pathway is required.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $retained;
    }

    private function carryForwardDocuments(Collection $retained, Collection $uploaded, int $round): void
    {
        if ($retained->pluck('content_sha256')->filter()->intersect($uploaded->pluck('content_sha256'))->isNotEmpty()) {
            throw ValidationException::withMessages(['documents' => 'The same evidence file cannot appear twice in an evidence round.']);
        }
        foreach ($retained->values() as $index => $document) {
            // Reuse the private file; rollback cleanup receives only newly uploaded paths.
            $copy = $document->replicate();
            $copy->fill([
                'submission_round' => $round,
                'display_order' => $uploaded->count() + $index,
                'superseded_at' => null,
            ]);
            $copy->save();
        }
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
            || ! $guardian->isParentVerificationApproved()) {
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
