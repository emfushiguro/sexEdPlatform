<?php

namespace App\Services\Admin;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserRelationshipService
{
    public function __construct(private readonly AdminActivityLogService $activityLogService)
    {
    }

    public function attachParentChild(array $payload, int $actorId, ?Request $request = null): ParentChildAccount
    {
        return DB::transaction(function () use ($payload, $actorId, $request): ParentChildAccount {
            $parentId = (int) $payload['parent_user_id'];
            $childId = (int) $payload['child_user_id'];

            if ($parentId === $childId) {
                throw new InvalidArgumentException('Parent and child accounts must be different users.');
            }

            $parent = User::query()->findOrFail($parentId);
            $child = User::query()->findOrFail($childId);

            if ($parent->birthdate && $parent->calculateAge() !== null && $parent->calculateAge() < 18) {
                throw new InvalidArgumentException('Selected parent account is not eligible for parent linkage.');
            }

            $existing = ParentChildAccount::query()
                ->where('parent_user_id', $parentId)
                ->where('child_user_id', $childId)
                ->first();

            if ($existing) {
                throw new InvalidArgumentException('This parent-child relationship already exists.');
            }

            $relationship = ParentChildAccount::query()->create([
                'parent_user_id' => $parentId,
                'child_user_id' => $childId,
                'can_view_progress' => (bool) ($payload['can_view_progress'] ?? true),
                'can_view_quiz_answers' => (bool) ($payload['can_view_quiz_answers'] ?? true),
                'can_approve_content' => (bool) ($payload['can_approve_content'] ?? false),
                'relationship_verified_at' => (bool) ($payload['is_verified'] ?? false) ? now() : null,
            ]);

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
                ],
                request: $request,
                adminUserId: $actorId,
            );

            return $relationship->load(['parent', 'child']);
        });
    }

    public function detachParentChild(int $parentId, int $childId, int $actorId, ?Request $request = null): void
    {
        DB::transaction(function () use ($parentId, $childId, $actorId, $request): void {
            $relationship = ParentChildAccount::query()
                ->where('parent_user_id', $parentId)
                ->where('child_user_id', $childId)
                ->first();

            if (! $relationship) {
                throw new InvalidArgumentException('Parent-child relationship was not found.');
            }

            $before = $relationship->toArray();
            $relationship->delete();

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
                adminUserId: $actorId,
            );
        });
    }

    public function setRelationshipVerification(int $parentId, int $childId, bool $isVerified, int $actorId, ?Request $request = null): ParentChildAccount
    {
        return DB::transaction(function () use ($parentId, $childId, $isVerified, $actorId, $request): ParentChildAccount {
            $relationship = ParentChildAccount::query()
                ->where('parent_user_id', $parentId)
                ->where('child_user_id', $childId)
                ->first();

            if (! $relationship) {
                throw new InvalidArgumentException('Parent-child relationship was not found.');
            }

            $before = $relationship->toArray();

            $relationship->update([
                'relationship_verified_at' => $isVerified ? now() : null,
            ]);

            $this->activityLogService->logModelMutation(
                action: $isVerified ? 'users.relationship.verify' : 'users.relationship.unverify',
                entity: $relationship,
                before: $before,
                after: $relationship->fresh()->toArray(),
                meta: [
                    'source' => 'admin.users.relationship.verification',
                    'parent_user_id' => $parentId,
                    'child_user_id' => $childId,
                    'is_verified' => $isVerified,
                ],
                request: $request,
                adminUserId: $actorId,
            );

            return $relationship->fresh(['parent', 'child']);
        });
    }
}
