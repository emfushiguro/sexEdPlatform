<?php

namespace App\Services\Auth;

use App\Models\MinorAccountHandoff;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Support\LearnerAgePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class MinorAccountHandoffService
{
    public function __construct(
        private readonly LearnerIdentityRequirement $identityRequirement,
    ) {}

    /** @return array{0: MinorAccountHandoff, 1: string} */
    public function issueActivation(ParentChildAccount $relationship): array
    {
        return DB::transaction(function () use ($relationship): array {
            $lockedRelationship = ParentChildAccount::query()
                ->lockForUpdate()
                ->findOrFail($relationship->id);
            $child = User::query()->lockForUpdate()->findOrFail($lockedRelationship->child_user_id);

            $this->assertActivationEligible($lockedRelationship, $child);

            MinorAccountHandoff::query()
                ->where('child_user_id', $child->id)
                ->where('purpose', MinorAccountHandoff::PURPOSE_ACTIVATION)
                ->whereNull('used_at')
                ->update(['expires_at' => now()]);

            $rawCode = $this->generateCode();
            $handoff = MinorAccountHandoff::query()->create([
                'child_user_id' => $child->id,
                'guardian_user_id' => $lockedRelationship->parent_user_id,
                'parent_child_account_id' => $lockedRelationship->id,
                'purpose' => MinorAccountHandoff::PURPOSE_ACTIVATION,
                'token_hash' => $this->digest($rawCode),
                'approved_at' => now(),
                'expires_at' => now()->addMinutes((int) config('learner_age_policy.handoff_minutes')),
            ]);

            return [$handoff->fresh(['child', 'guardian', 'relationship']), $rawCode];
        });
    }

    public function findUsable(string $code, string $purpose = MinorAccountHandoff::PURPOSE_ACTIVATION): ?MinorAccountHandoff
    {
        if (! in_array($purpose, [MinorAccountHandoff::PURPOSE_ACTIVATION, MinorAccountHandoff::PURPOSE_PASSWORD_RESET], true)) {
            return null;
        }

        $normalized = $this->normalizeCode($code);
        if ($normalized === '') {
            return null;
        }

        return MinorAccountHandoff::query()
            ->with(['child.learnerProfile', 'guardian', 'relationship'])
            ->where('purpose', $purpose)
            ->where('token_hash', $this->digest($normalized))
            ->whereNotNull('approved_at')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function consumeActivation(string $code, string $username, string $password): User
    {
        return DB::transaction(function () use ($code, $username, $password): User {
            $handoff = MinorAccountHandoff::query()
                ->where('purpose', MinorAccountHandoff::PURPOSE_ACTIVATION)
                ->where('token_hash', $this->digest($this->normalizeCode($code)))
                ->lockForUpdate()
                ->first();

            if ($handoff === null || $handoff->approved_at === null || $handoff->used_at !== null || $handoff->expires_at?->isPast()) {
                throw new InvalidArgumentException('This activation code is invalid or expired.');
            }

            $relationship = ParentChildAccount::query()->lockForUpdate()->find($handoff->parent_child_account_id);
            $child = User::query()->lockForUpdate()->find($handoff->child_user_id);
            if ($relationship === null || $child === null
                || (int) $relationship->parent_user_id !== (int) $handoff->guardian_user_id
                || (int) $relationship->child_user_id !== (int) $child->id) {
                throw new InvalidArgumentException('This activation code is invalid or expired.');
            }

            $this->assertActivationEligible($relationship, $child);

            $profile = $child->learnerProfile()->first();
            if ($profile === null) {
                throw new InvalidArgumentException('This activation code is invalid or expired.');
            }

            $child->forceFill([
                'password' => Hash::make($password),
                'minor_account_activation_required' => false,
                'minor_account_activated_at' => now(),
                'minor_privacy_notice_version' => config('learner_age_policy.guardian_consent_version'),
                'minor_privacy_accepted_at' => now(),
            ])->save();
            $profile->forceFill(['username' => $username])->save();
            $this->identityRequirement->createForNewLearner($child->fresh());

            $handoff->forceFill(['used_at' => now()])->save();

            return $child->fresh(['learnerProfile']);
        });
    }

    private function assertActivationEligible(ParentChildAccount $relationship, User $child): void
    {
        if (LearnerAgePolicy::classify($child->birthdate) !== LearnerAgePolicy::ELIGIBLE_TEENAGER
            || ! $child->minor_account_activation_required
            || ! ParentChildAccount::query()->whereKey($relationship->id)->minorAccessEligible()->exists()) {
            throw new InvalidArgumentException('This activation code is invalid or expired.');
        }
    }

    private function generateCode(): string
    {
        return strtoupper(Str::random(16));
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $code));
    }

    private function digest(string $code): string
    {
        return hash_hmac('sha256', $this->normalizeCode($code), (string) config('app.key'));
    }
}
