<?php

namespace App\Services;

use App\Enums\ParentChildInvitationStatus;
use App\Models\ParentChildAccount;
use App\Models\ParentChildInvitation;
use App\Models\User;
use App\Notifications\Learner\ParentChildInvitationReceivedNotification;
use App\Notifications\Parent\ParentChildInvitationRespondedNotification;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ParentChildInvitationService
{
    public function __construct(
        private readonly GuardianRelationshipVerificationService $relationshipVerificationService,
        private readonly GuardianRelationshipEvidenceService $evidence,
    ) {}

    public function sendInvitation(
        User $parent,
        string $identifier,
        string $relationshipType,
        ?string $relationshipCustom = null,
        ?string $message = null,
        ?array $verificationPayload = null,
    ): ParentChildInvitation {
        $child = $this->resolveChildFromIdentifier($identifier);

        if (! $child || ! $child->isLearner()) {
            throw new InvalidArgumentException('No learner account matches that username or email.');
        }

        if ((int) $parent->id === (int) $child->id) {
            throw new InvalidArgumentException('You cannot invite your own account.');
        }

        $requiresVerification = GuardianRelationshipTypes::requiresVerification($relationshipType);
        $documents = $this->normalizeVerificationDocuments($verificationPayload);
        if ($requiresVerification && $documents === []) {
            throw new InvalidArgumentException('Supporting documentation is required for this relationship.');
        }

        $stagedPaths = [];
        $seenHashes = [];

        try {
            $invitation = DB::transaction(function () use ($parent, $child, $relationshipType, $relationshipCustom, $message, $requiresVerification, $documents, &$stagedPaths, &$seenHashes): ParentChildInvitation {
                User::query()
                    ->lockForUpdate()
                    ->findOrFail($child->id);

                $existingRelationship = ParentChildAccount::withTrashed()
                    ->where('parent_user_id', $parent->id)
                    ->where('child_user_id', $child->id)
                    ->first();

                if ($existingRelationship && $existingRelationship->deleted_at === null && $existingRelationship->verification_status === 'approved') {
                    throw new InvalidArgumentException('This learner is already linked to your guardian account.');
                }

                if ($existingRelationship && $existingRelationship->deleted_at === null && $existingRelationship->verification_status === 'pending') {
                    throw new InvalidArgumentException('A relationship verification request is already pending for this learner.');
                }

                $existingPending = ParentChildInvitation::query()
                    ->where('inviter_parent_user_id', $parent->id)
                    ->where('child_user_id', $child->id)
                    ->where('status', ParentChildInvitationStatus::Pending->value)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($existingPending !== null) {
                    if ($existingPending->isExpired()) {
                        $existingPending->update(['status' => ParentChildInvitationStatus::Expired->value]);
                    } else {
                        throw new InvalidArgumentException('An invitation is already pending for this learner.');
                    }
                }

                $invitation = ParentChildInvitation::query()->create([
                    'inviter_parent_user_id' => $parent->id,
                    'child_user_id' => $child->id,
                    'relationship_type' => $relationshipType,
                    'relationship_custom' => $relationshipType === GuardianRelationshipTypes::OTHER ? trim((string) $relationshipCustom) : null,
                    'invite_token' => (string) Str::uuid(),
                    'status' => ParentChildInvitationStatus::Pending->value,
                    'message' => $message ? trim($message) : null,
                    'expires_at' => now()->addDays(14),
                ]);

                if ($requiresVerification) {
                    $stagedDocuments = collect($documents)->values()->map(function (array $document, int $index) use ($invitation, &$stagedPaths, &$seenHashes): array {
                        $staged = $this->stagedDocument(
                            (string) $document['document_type'],
                            $document['file'],
                            $invitation,
                            (string) ($document['document_side'] ?? 'not_applicable'),
                            $document['pairing_key'] ?? null,
                            (int) ($document['display_order'] ?? $index),
                        );
                        $stagedPaths[] = $staged['path'];

                        if (isset($seenHashes[$staged['content_sha256']])) {
                            throw new InvalidArgumentException('Duplicate evidence files are not allowed.');
                        }

                        $seenHashes[$staged['content_sha256']] = true;

                        return $staged;
                    })->all();

                    $invitation->update(['relationship_verification_documents' => $stagedDocuments]);
                }

                return $invitation;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($stagedPaths);

            throw $exception;
        }

        $invitation->load(['inviterParent:id,name', 'child:id,name']);
        $child->notify(new ParentChildInvitationReceivedNotification($invitation));

        return $invitation;
    }

    public function respondToInvitation(User $child, ParentChildInvitation $invitation, string $decision, ?string $note = null): ParentChildInvitation
    {
        if ((int) $invitation->child_user_id !== (int) $child->id) {
            throw new InvalidArgumentException('You are not allowed to respond to this invitation.');
        }

        $normalizedDecision = trim(strtolower($decision));
        if (! in_array($normalizedDecision, ['accept', 'reject'], true)) {
            throw new InvalidArgumentException('Invalid invitation decision.');
        }

        $decisionNote = $note ? trim($note) : null;

        $movedDocuments = [];

        try {
            [$updatedInvitation, $wasExpired] = DB::transaction(function () use ($child, $invitation, $normalizedDecision, $decisionNote, &$movedDocuments): array {
                $lockedChild = User::query()->lockForUpdate()->findOrFail($child->id);
                $invitation = ParentChildInvitation::query()
                    ->lockForUpdate()
                    ->findOrFail($invitation->id);

                if (($invitation->status instanceof ParentChildInvitationStatus ? $invitation->status->value : (string) $invitation->status) !== ParentChildInvitationStatus::Pending->value) {
                    throw new InvalidArgumentException('This invitation is no longer pending.');
                }

                if ($invitation->isExpired()) {
                    $stagedDocuments = $invitation->relationship_verification_documents;
                    $invitation->update(['status' => ParentChildInvitationStatus::Expired->value]);
                    $invitation->update(['relationship_verification_documents' => null]);
                    $this->scheduleStagedDocumentCleanup($stagedDocuments, (int) $invitation->id);

                    return [$invitation->fresh(), true];
                }

                if ((int) $invitation->child_user_id !== (int) $lockedChild->id) {
                    throw new InvalidArgumentException('You are not allowed to respond to this invitation.');
                }

                if ($normalizedDecision === 'accept') {
                    $link = ParentChildAccount::withTrashed()
                        ->lockForUpdate()
                        ->where('parent_user_id', $invitation->inviter_parent_user_id)
                        ->where('child_user_id', $invitation->child_user_id)
                        ->first();

                    if ($link && $link->deleted_at === null && $link->verification_status === 'approved') {
                        throw new InvalidArgumentException('This learner is already linked to the guardian account.');
                    }

                    if ($link && $link->deleted_at === null && $link->verification_status === 'pending') {
                        throw new InvalidArgumentException('A relationship verification request is already pending for this learner.');
                    }

                    $relationshipType = $invitation->relationship_type ?: GuardianRelationshipTypes::LEGACY_PARENT;
                    $requiresVerification = GuardianRelationshipTypes::requiresVerification($relationshipType);
                    $documents = $requiresVerification ? $invitation->relationship_verification_documents : null;

                    if ($requiresVerification && (! is_array($documents) || $documents === [])) {
                        throw new InvalidArgumentException('A staged verification document is missing.');
                    }

                    $payload = [
                        'can_view_progress' => true,
                        'can_view_quiz_answers' => true,
                        'can_approve_content' => false,
                        'relationship_type' => $relationshipType,
                        'relationship_custom' => $invitation->relationship_custom,
                        'verification_pathway' => GuardianRelationshipTypes::pathway($relationshipType),
                        'relationship_status' => 'pending',
                        'relationship_verified_status' => $requiresVerification ? 'pending' : $this->relationshipVerificationService->initialStatus($relationshipType),
                        'current_evidence_round' => 0,
                        'is_legacy_relationship' => ! $requiresVerification,
                        'verification_status' => 'pending',
                        'verification_rejection_reason' => null,
                        'verification_reviewed_by' => null,
                        'verification_reviewed_at' => null,
                        'verification_approved_at' => null,
                        'verification_document_path' => null,
                        'relationship_verified_at' => null,
                    ];

                    if ($link) {
                        if ($link->trashed()) {
                            $link->restore();
                        }

                        $link->update($payload);
                        $relationship = $link;
                    } else {
                        $relationship = ParentChildAccount::query()->create([
                            'parent_user_id' => $invitation->inviter_parent_user_id,
                            'child_user_id' => $invitation->child_user_id,
                            ...$payload,
                        ]);
                    }

                    if ($requiresVerification) {
                        $this->relationshipVerificationService->submitStaged(
                            $relationship,
                            $invitation->inviterParent()->firstOrFail(),
                            $documents,
                            function (ParentChildAccount $submittedRelationship) use ($invitation, $decisionNote): void {
                                $invitation->update([
                                    'parent_child_account_id' => $submittedRelationship->id,
                                    'status' => ParentChildInvitationStatus::Accepted->value,
                                    'decision_note' => $decisionNote,
                                    'responded_at' => now(),
                                    'relationship_verification_documents' => null,
                                ]);
                            },
                            $movedDocuments,
                        );
                    } else {
                        $this->relationshipVerificationService->submitDeclaration(
                            $relationship,
                            $invitation->inviterParent()->firstOrFail(),
                            function (ParentChildAccount $submittedRelationship) use ($invitation, $decisionNote): void {
                                $invitation->update([
                                    'parent_child_account_id' => $submittedRelationship->id,
                                    'status' => ParentChildInvitationStatus::Accepted->value,
                                    'decision_note' => $decisionNote,
                                    'responded_at' => now(),
                                    'relationship_verification_documents' => null,
                                ]);
                            },
                        );
                    }

                } else {
                    $stagedDocuments = $invitation->relationship_verification_documents;
                    $invitation->update([
                        'status' => ParentChildInvitationStatus::Rejected->value,
                        'decision_note' => $decisionNote,
                        'responded_at' => now(),
                        'relationship_verification_documents' => null,
                    ]);
                    $this->scheduleStagedDocumentCleanup($stagedDocuments, (int) $invitation->id);
                }

                return [$invitation->fresh(['inviterParent:id,name', 'child:id,name']), false];
            });
        } catch (\Throwable $exception) {
            $this->evidence->restoreStagedDocuments($movedDocuments);

            throw $exception;
        }

        if ($wasExpired) {
            throw new InvalidArgumentException('This invitation has already expired.');
        }

        $updatedInvitation->inviterParent?->notify(new ParentChildInvitationRespondedNotification($updatedInvitation));

        return $updatedInvitation;
    }

    public function cancelInvitation(User $parent, ParentChildInvitation $invitation): ParentChildInvitation
    {
        if ((int) $invitation->inviter_parent_user_id !== (int) $parent->id) {
            throw new InvalidArgumentException('You are not allowed to cancel this invitation.');
        }

        return DB::transaction(function () use ($parent, $invitation): ParentChildInvitation {
            $locked = ParentChildInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
            if (($locked->status instanceof ParentChildInvitationStatus ? $locked->status->value : (string) $locked->status) !== ParentChildInvitationStatus::Pending->value) {
                throw new InvalidArgumentException('Only pending invitations can be cancelled.');
            }

            $stagedDocuments = $locked->relationship_verification_documents;
            $locked->update([
                'status' => ParentChildInvitationStatus::Cancelled->value,
                'responded_at' => now(),
                'relationship_verification_documents' => null,
            ]);
            $this->scheduleStagedDocumentCleanup($stagedDocuments, (int) $locked->id);

            return $locked->fresh();
        });
    }

    public function getOutgoingInvitations(User $parent): Collection
    {
        $invitations = ParentChildInvitation::query()
            ->where('inviter_parent_user_id', $parent->id)
            ->with([
                'inviterParent:id,name,status,parent_verification_status,created_at',
                'inviterParent.learnerProfile:id,user_id,avatar_path',
                'child:id,name,status',
                'child.learnerProfile:id,user_id,username,avatar_path',
            ])
            ->latest('id')
            ->get();

        $this->expirePendingInvitations($invitations);

        return $invitations;
    }

    public function getIncomingInvitations(User $child): Collection
    {
        $invitations = ParentChildInvitation::query()
            ->where('child_user_id', $child->id)
            ->with([
                'inviterParent:id,name,status,parent_verification_status,created_at',
                'inviterParent.learnerProfile:id,user_id,avatar_path',
            ])
            ->latest('id')
            ->get();

        $this->expirePendingInvitations($invitations);

        return $invitations;
    }

    private function resolveChildFromIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $normalized = strtolower($identifier);

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->orWhereHas('learnerProfile', function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(username) = ?', [$normalized]);
            })
            ->first();
    }

    private function stagedDocument(
        string $documentType,
        UploadedFile $file,
        ParentChildInvitation $invitation,
        string $documentSide = 'not_applicable',
        ?string $pairingKey = null,
        int $displayOrder = 0,
    ): array
    {
        if (! $file instanceof UploadedFile) {
            throw new InvalidArgumentException('Every relationship invitation requires a valid evidence file.');
        }

        $path = $file->store("guardian-relationship-invitations/{$invitation->id}", 'local');
        if (! is_string($path)) {
            throw new InvalidArgumentException('Unable to stage relationship evidence.');
        }

        $hash = hash_file('sha256', Storage::disk('local')->path($path));

        return [
            'document_type' => $documentType,
            'document_side' => $documentSide,
            'pairing_key' => $pairingKey,
            'display_order' => $displayOrder,
            'content_sha256' => $hash,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize() ?: 0,
        ];
    }

    private function expirePendingInvitations(Collection $invitations): void
    {
        $invitations
            ->filter(fn (ParentChildInvitation $invitation) => $invitation->isPending() && $invitation->isExpired())
            ->each(function (ParentChildInvitation $invitation): void {
                DB::transaction(function () use ($invitation): void {
                    $locked = ParentChildInvitation::query()->lockForUpdate()->findOrFail($invitation->id);
                    if (! $locked->isPending() || ! $locked->isExpired()) {
                        return;
                    }

                    $stagedDocuments = $locked->relationship_verification_documents;
                    $locked->update([
                        'status' => ParentChildInvitationStatus::Expired->value,
                        'relationship_verification_documents' => null,
                    ]);
                    $this->scheduleStagedDocumentCleanup($stagedDocuments, (int) $locked->id);
                });

                $invitation->status = ParentChildInvitationStatus::Expired;
            });
    }

    private function normalizeVerificationDocuments(?array $verificationPayload): array
    {
        if (! is_array($verificationPayload)) {
            return [];
        }

        if (isset($verificationPayload['documents']) && is_array($verificationPayload['documents'])) {
            return array_values(array_filter(
                $verificationPayload['documents'],
                static fn (mixed $document): bool => is_array($document),
            ));
        }

        if (($verificationPayload['document'] ?? null) instanceof UploadedFile) {
            return array_values(array_filter([
                [
                    'document_type' => (string) ($verificationPayload['document_type'] ?? ''),
                    'document_side' => 'not_applicable',
                    'pairing_key' => null,
                    'file' => $verificationPayload['document'],
                ],
                ($verificationPayload['supporting_document'] ?? null) instanceof UploadedFile ? [
                    'document_type' => 'other_supporting_document',
                    'document_side' => 'not_applicable',
                    'pairing_key' => null,
                    'file' => $verificationPayload['supporting_document'],
                ] : null,
            ]));
        }

        return [];
    }

    private function scheduleStagedDocumentCleanup(?array $documents, int $invitationId): void
    {
        if (! is_array($documents) || $documents === []) {
            return;
        }

        DB::afterCommit(function () use ($documents, $invitationId): void {
            $disk = Storage::disk('local');
            foreach ($documents as $document) {
                $path = is_array($document) ? (string) ($document['path'] ?? '') : '';
                if ($path === '' || ! str_starts_with($path, 'guardian-relationship-invitations/')) {
                    continue;
                }

                try {
                    $disk->delete($path);
                } catch (\Throwable) {
                    \Illuminate\Support\Facades\Log::error('Unable to remove staged invitation document after status commit.', [
                        'invitation_id' => $invitationId,
                        'path' => $path,
                    ]);
                }
            }
        });
    }
}
