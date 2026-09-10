<?php

namespace App\Services\Admin;

use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\RelationshipVerificationStatusNotification;
use App\Services\AdminActivityLogService;
use App\Services\GuardianRelationshipVerificationService;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class UserRelationshipService
{
    public function __construct(
        private readonly AdminActivityLogService $activityLogService,
        private readonly GuardianRelationshipVerificationService $relationshipVerificationService,
    ) {}

    public function attachParentChild(array $payload, int $actorId, ?Request $request = null): ParentChildAccount
    {
        return DB::transaction(function () use ($payload, $actorId, $request): ParentChildAccount {
            $parentId = (int) $payload['parent_user_id'];
            $childId = (int) $payload['child_user_id'];

            if ($parentId === $childId) {
                throw new InvalidArgumentException('Guardian and dependent accounts must be different users.');
            }

            $parent = User::query()->findOrFail($parentId);
            $child = User::query()->findOrFail($childId);

            if ($parent->birthdate && $parent->calculateAge() !== null && $parent->calculateAge() < 18) {
                throw new InvalidArgumentException('Selected guardian account is not eligible for guardian linkage.');
            }

            $relationship = ParentChildAccount::withTrashed()
                ->where('parent_user_id', $parentId)
                ->where('child_user_id', $childId)
                ->lockForUpdate()
                ->first();

            if ($relationship && ! $relationship->trashed()) {
                throw new InvalidArgumentException('This guardian-dependent relationship already exists.');
            }

            $relationshipType = (string) $payload['relationship_type'];
            $attributes = [
                'relationship_type' => $relationshipType,
                'relationship_custom' => $relationshipType === GuardianRelationshipTypes::OTHER
                    ? trim((string) ($payload['relationship_custom'] ?? ''))
                    : null,
                'verification_pathway' => GuardianRelationshipTypes::pathway($relationshipType),
                'relationship_status' => ParentChildAccount::STATUS_PENDING,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
                'relationship_notes' => $payload['relationship_notes'] ?? null,
                'can_view_progress' => (bool) ($payload['can_view_progress'] ?? true),
                'can_view_quiz_answers' => (bool) ($payload['can_view_quiz_answers'] ?? true),
                'can_approve_content' => false,
                'can_manage_support_information' => false,
                'relationship_verified_at' => null,
                'is_legacy_relationship' => false,
            ];

            if ($relationship) {
                $relationship->restore();
                $relationship->update([
                    ...$attributes,
                    'relationship_verification_submitted_at' => null,
                    'relationship_verification_reviewed_by' => null,
                    'relationship_verification_reviewed_at' => null,
                    'relationship_verification_rejection_reason' => null,
                    'relationship_verification_rejection_note' => null,
                    'relationship_verification_revoked_at' => null,
                    'relationship_deactivated_at' => null,
                ]);
            } else {
                $relationship = ParentChildAccount::query()->create([
                    'parent_user_id' => $parentId,
                    'child_user_id' => $childId,
                    'current_evidence_round' => 0,
                    ...$attributes,
                ]);
            }

            $relationship->refresh();
            $this->audit(
                relationship: $relationship,
                actorId: $actorId,
                action: 'claim_created',
                previousStatus: null,
                newStatus: ParentChildAccount::VERIFICATION_PENDING,
                notes: 'Administrative relationship claim created.',
            );
            $this->notifyPartiesAfterCommit($relationship->id, 'claim_created');

            $parent->refreshClassificationCache();
            $child->refreshClassificationCache();

            $this->activityLogService->logModelMutation(
                action: 'users.relationship.attach',
                entity: $relationship,
                before: null,
                after: $relationship->toArray(),
                meta: [
                    'source' => 'admin.users.relationship.attach',
                    'parent_user_id' => $parentId,
                    'child_user_id' => $childId,
                    'relationship_type' => $relationship->relationship_type,
                    'relationship_status' => $relationship->relationship_status,
                ],
                request: $request,
                adminUserId: $actorId,
            );

            return $relationship->load(['parent', 'child']);
        });
    }

    public function detachParentChild(
        int $parentId,
        int $childId,
        User $admin,
        ?string $note = null,
        ?Request $request = null,
    ): void {
        DB::transaction(function () use ($parentId, $childId, $admin, $note, $request): void {
            $relationship = ParentChildAccount::query()
                ->where('parent_user_id', $parentId)
                ->where('child_user_id', $childId)
                ->lockForUpdate()
                ->first();

            if (! $relationship) {
                throw new InvalidArgumentException('Guardian-dependent relationship was not found.');
            }

            if (in_array($relationship->relationship_status, [
                ParentChildAccount::STATUS_REJECTED,
                ParentChildAccount::STATUS_REVOKED,
                ParentChildAccount::STATUS_INACTIVE,
            ], true)) {
                throw new InvalidArgumentException('This relationship is already closed and cannot be detached.');
            }

            $before = $relationship->toArray();

            if ($relationship->isVerifiedActive()) {
                $this->relationshipVerificationService->deactivate($relationship, $admin, $note);
            } elseif ($relationship->relationship_verified_status === ParentChildAccount::VERIFICATION_UNDER_REVIEW) {
                $this->relationshipVerificationService->reject($relationship, $admin, 'claim_closed', $note, false);
            } elseif (in_array($relationship->relationship_verified_status, [
                ParentChildAccount::VERIFICATION_PENDING,
                ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
            ], true)) {
                $this->relationshipVerificationService->closePendingClaim($relationship, $admin, $note);
            }

            $relationship->refresh()->delete();

            User::query()->find($parentId)?->refreshClassificationCache();
            User::query()->find($childId)?->refreshClassificationCache();

            $this->activityLogService->log(
                action: 'users.relationship.detach',
                entityType: ParentChildAccount::class,
                entityId: $before['id'] ?? null,
                before: $before,
                after: null,
                meta: [
                    'source' => 'admin.users.relationship.detach',
                    'parent_user_id' => $parentId,
                    'child_user_id' => $childId,
                ],
                request: $request,
                adminUserId: $admin->id,
            );
        });
    }

    public function updateParentChildPermissions(
        int $parentId,
        int $childId,
        array $permissions,
        User $admin,
        ?Request $request = null,
    ): ParentChildAccount {
        if ($admin->status !== User::STATUS_ACTIVE || ! $admin->hasRole('admin')) {
            throw new InvalidArgumentException('An active administrator is required.');
        }

        return DB::transaction(function () use ($parentId, $childId, $permissions, $admin, $request): ParentChildAccount {
            $relationship = ParentChildAccount::query()
                ->where('parent_user_id', $parentId)
                ->where('child_user_id', $childId)
                ->lockForUpdate()
                ->first();

            if (! $relationship) {
                throw new InvalidArgumentException('Guardian-dependent relationship was not found.');
            }

            if (! $relationship->isVerifiedActive()) {
                throw new InvalidArgumentException('Only an active verified relationship can have permissions updated.');
            }

            $before = $relationship->only([
                'can_view_progress',
                'can_view_quiz_answers',
                'can_approve_content',
            ]);
            $relationship->update([
                'can_view_progress' => (bool) $permissions['can_view_progress'],
                'can_view_quiz_answers' => (bool) $permissions['can_view_quiz_answers'],
                'can_approve_content' => (bool) $permissions['can_approve_content'],
            ]);
            $relationship->refresh();

            $this->activityLogService->logModelMutation(
                action: 'users.relationship.permissions_updated',
                entity: $relationship,
                before: $before,
                after: $relationship->only([
                    'can_view_progress',
                    'can_view_quiz_answers',
                    'can_approve_content',
                ]),
                meta: [
                    'source' => 'admin.users.relationship.permissions',
                    'parent_user_id' => $parentId,
                    'child_user_id' => $childId,
                ],
                request: $request,
                adminUserId: $admin->id,
            );

            GuardianRelationshipVerificationAudit::query()->create([
                'parent_child_account_id' => $relationship->id,
                'actor_user_id' => $admin->id,
                'action' => 'permissions_updated',
                'previous_status' => $relationship->relationship_verified_status,
                'new_status' => $relationship->relationship_verified_status,
                'submission_round' => $relationship->current_evidence_round,
                'notes' => json_encode([
                    'before' => $before,
                    'after' => $relationship->only([
                        'can_view_progress',
                        'can_view_quiz_answers',
                        'can_approve_content',
                    ]),
                ], JSON_THROW_ON_ERROR),
            ]);

            return $relationship->load(['parent', 'child']);
        });
    }

    private function audit(
        ParentChildAccount $relationship,
        int $actorId,
        string $action,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $notes = null,
    ): void {
        GuardianRelationshipVerificationAudit::query()->create([
            'parent_child_account_id' => $relationship->id,
            'actor_user_id' => $actorId,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'submission_round' => $relationship->current_evidence_round,
            'notes' => $notes,
        ]);
    }

    private function notifyPartiesAfterCommit(int $relationshipId, string $action): void
    {
        DB::afterCommit(function () use ($relationshipId, $action): void {
            try {
                $relationship = ParentChildAccount::query()->with(['parent', 'child'])->find($relationshipId);

                if (! $relationship) {
                    return;
                }

                foreach ([$relationship->parent, $relationship->child] as $target) {
                    if (! $target) {
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
