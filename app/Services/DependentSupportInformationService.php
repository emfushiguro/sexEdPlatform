<?php

namespace App\Services;

use App\Models\DependentSupportInformationAudit;
use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DependentSupportInformationService
{
    public function save(
        User $dependent,
        User $actor,
        array $payload,
        ?string $expectedUpdatedAt,
    ): DependentSupportProfile {
        $dependentId = (int) $dependent->id;
        $operation = function () use ($dependent, $actor, $payload, $expectedUpdatedAt): DependentSupportProfile {
            $profile = DependentSupportProfile::query()
                ->where('dependent_user_id', $dependent->id)
                ->lockForUpdate()
                ->first();

            if ($profile) {
                Gate::forUser($actor)->authorize('update', $profile);
                $this->assertFresh($profile, $expectedUpdatedAt);
            } else {
                Gate::forUser($actor)->authorize('create', [DependentSupportProfile::class, $dependent]);

                if ($expectedUpdatedAt !== null) {
                    throw ValidationException::withMessages([
                        'support_information' => 'This information changed. Reload and review the latest version.',
                    ]);
                }
            }

            $values = $this->contentValues($payload);
            $this->assertHasContent($values);
            $changedFields = $this->changedFields($profile, $values);

            if ($profile) {
                $profile->update([
                    ...$values,
                    'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                    'purpose_acknowledged_at' => now(),
                    'purpose_acknowledged_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);
                $action = DependentSupportInformationAudit::ACTION_UPDATED;
            } else {
                $profile = DependentSupportProfile::query()->create([
                    'dependent_user_id' => $dependent->id,
                    ...$values,
                    'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                    'purpose_acknowledged_at' => now(),
                    'purpose_acknowledged_by_user_id' => $actor->id,
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);
                $action = DependentSupportInformationAudit::ACTION_CREATED;
            }

            $this->audit($dependent->id, $actor->id, null, $action, $changedFields);

            return $profile->fresh();
        };

        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            if ($this->isDependentUniqueConflict($exception, $dependentId)) {
                throw ValidationException::withMessages([
                    'support_information' => 'This information changed. Reload and review the latest version.',
                ]);
            }

            throw $exception;
        }
    }

    public function saveDuringRegistration(
        ParentChildAccount $relationship,
        User $guardian,
        array $payload,
    ): DependentSupportProfile {
        $dependentId = (int) $relationship->child_user_id;
        $operation = function () use ($relationship, $guardian, $payload): DependentSupportProfile {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);

            if ((int) $locked->parent_user_id !== (int) $guardian->id
                || $locked->trashed()
                || in_array($locked->relationship_status, [
                    ParentChildAccount::STATUS_REJECTED,
                    ParentChildAccount::STATUS_REVOKED,
                    ParentChildAccount::STATUS_INACTIVE,
                ], true)) {
                throw new AuthorizationException('The dependent setup link is no longer available.');
            }

            if (DependentSupportProfile::query()->where('dependent_user_id', $locked->child_user_id)->exists()) {
                throw ValidationException::withMessages([
                    'support_information' => 'Support information already exists. Use the dependent settings after verification.',
                ]);
            }

            $values = $this->contentValues($payload);
            $this->assertHasContent($values);
            $profile = DependentSupportProfile::query()->create([
                'dependent_user_id' => $locked->child_user_id,
                ...$values,
                'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                'purpose_acknowledged_at' => now(),
                'purpose_acknowledged_by_user_id' => $guardian->id,
                'created_by_user_id' => $guardian->id,
                'updated_by_user_id' => $guardian->id,
            ]);

            $this->audit(
                $locked->child_user_id,
                $guardian->id,
                $locked->id,
                DependentSupportInformationAudit::ACTION_CREATED,
                array_keys(array_filter($values, fn (?string $value): bool => $value !== null)),
            );

            return $profile->fresh();
        };

        try {
            return DB::transaction($operation);
        } catch (QueryException $exception) {
            if ($this->isDependentUniqueConflict($exception, $dependentId)) {
                throw ValidationException::withMessages([
                    'support_information' => 'This information changed. Reload and review the latest version.',
                ]);
            }

            throw $exception;
        }
    }

    public function remove(
        DependentSupportProfile $profile,
        User $actor,
        string $expectedUpdatedAt,
    ): void {
        DB::transaction(function () use ($profile, $actor, $expectedUpdatedAt): void {
            $locked = DependentSupportProfile::query()->lockForUpdate()->findOrFail($profile->id);
            Gate::forUser($actor)->authorize('delete', $locked);
            $this->assertFresh($locked, $expectedUpdatedAt);

            $relationshipId = $this->relationshipIdFor($actor, (int) $locked->dependent_user_id);
            $dependentId = (int) $locked->dependent_user_id;
            $locked->delete();

            $this->audit(
                $dependentId,
                $actor->id,
                $relationshipId,
                DependentSupportInformationAudit::ACTION_REMOVED,
                null,
            );
        });
    }

    public function setGuardianAccess(
        ParentChildAccount $relationship,
        User $dependent,
        bool $enabled,
    ): ParentChildAccount {
        return DB::transaction(function () use ($relationship, $dependent, $enabled): ParentChildAccount {
            $locked = ParentChildAccount::query()
                ->accessEligible()
                ->whereKey($relationship->id)
                ->where('child_user_id', $dependent->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || (int) $dependent->id !== (int) $locked->child_user_id) {
                throw new AuthorizationException('You cannot change this guardian relationship.');
            }

            if ((bool) $locked->can_manage_support_information === $enabled) {
                return $locked;
            }

            $locked->update(['can_manage_support_information' => $enabled]);
            $this->audit(
                $dependent->id,
                $dependent->id,
                $locked->id,
                $enabled
                    ? DependentSupportInformationAudit::ACTION_PERMISSION_GRANTED
                    : DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
                null,
            );

            return $locked->fresh();
        });
    }

    public function resetGuardianAccessForLifecycle(
        ParentChildAccount $relationship,
        User $actor,
    ): void {
        if (! $relationship->can_manage_support_information) {
            return;
        }

        $relationship->update(['can_manage_support_information' => false]);
        $this->audit(
            $relationship->child_user_id,
            $actor->id,
            $relationship->id,
            DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
            null,
        );
    }

    private function assertFresh(DependentSupportProfile $profile, ?string $expected): void
    {
        if ($expected === null || $expected !== $profile->getRawOriginal('updated_at')) {
            throw ValidationException::withMessages([
                'support_information' => 'This information changed. Reload and review the latest version.',
            ]);
        }
    }

    /** @return array<string, string|null> */
    private function contentValues(array $payload): array
    {
        return collect(DependentSupportProfile::CONTENT_FIELDS)
            ->mapWithKeys(function (string $field) use ($payload): array {
                $value = $payload[$field] ?? null;

                return [$field => is_string($value) && trim($value) !== '' ? trim($value) : null];
            })
            ->all();
    }

    /** @param array<string, string|null> $values */
    private function assertHasContent(array $values): void
    {
        if (! collect($values)->contains(fn (?string $value): bool => $value !== null)) {
            throw ValidationException::withMessages([
                'support_information' => 'Provide at least one relevant support detail.',
            ]);
        }
    }

    /** @param array<string, string|null> $values */
    private function changedFields(?DependentSupportProfile $profile, array $values): array
    {
        return collect($values)
            ->filter(fn (?string $value, string $field): bool => ! $profile || $profile->{$field} !== $value)
            ->keys()
            ->values()
            ->all();
    }

    private function relationshipIdFor(User $actor, int $dependentId): ?int
    {
        if ((int) $actor->id === $dependentId) {
            return null;
        }

        return ParentChildAccount::query()
            ->where('parent_user_id', $actor->id)
            ->where('child_user_id', $dependentId)
            ->value('id');
    }

    private function isDependentUniqueConflict(QueryException $exception, int $dependentId): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && str_contains($message, 'dependent_support_profiles')
            && DependentSupportProfile::query()
                ->where('dependent_user_id', $dependentId)
                ->exists();
    }

    private function audit(
        int $dependentId,
        ?int $actorId,
        ?int $relationshipId,
        string $action,
        ?array $changedFields,
    ): void {
        DependentSupportInformationAudit::query()->create([
            'dependent_user_id' => $dependentId,
            'actor_user_id' => $actorId,
            'parent_child_account_id' => $relationshipId,
            'action' => $action,
            'changed_fields' => $changedFields,
            'occurred_at' => now(),
        ]);
    }
}
