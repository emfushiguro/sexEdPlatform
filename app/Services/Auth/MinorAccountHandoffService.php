<?php

namespace App\Services\Auth;

use App\Models\MinorAccountHandoff;
use App\Models\AdultDependentAuthorityRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\TeenagerPasswordRecoveryRequestedNotification;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Support\LearnerAgePolicy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
                'token_hash' => $this->digestForStorage($rawCode),
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
            ->where('token_hash', $this->digestForStorage($normalized))
            ->whereNotNull('approved_at')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public function consumeActivation(string $code, string $username, string $password, bool $adultConsent = false, string $consentMethod = 'self'): User
    {
        return DB::transaction(function () use ($code, $username, $password, $adultConsent, $consentMethod): User {
            $handoff = MinorAccountHandoff::query()
                ->where('purpose', MinorAccountHandoff::PURPOSE_ACTIVATION)
                ->where('token_hash', $this->digestForStorage($this->normalizeCode($code)))
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

            $isMinor = LearnerAgePolicy::classify($child->birthdate) === LearnerAgePolicy::ELIGIBLE_TEENAGER;
            $authority = null;
            if (! $isMinor) {
                if ($consentMethod === 'representative') {
                    $authority = $this->approvedAdultAuthority($relationship);
                    if ($authority === null) {
                        throw new InvalidArgumentException('Representative authority has not been approved.');
                    }
                } elseif ($consentMethod !== 'self' || ! $adultConsent) {
                    throw new InvalidArgumentException('The dependent must personally accept the adult account notice.');
                }
            }
            if (! $isMinor && ! DB::table('dependent_adult_authorization_records')->where('dependent_user_id', $child->id)->exists()) {
                DB::table('dependent_adult_authorization_records')->insert([
                    'dependent_user_id' => $child->id,
                    'parent_child_account_id' => $relationship->id,
                    'authorization_method' => $authority ? 'representative' : 'self',
                    'authorized_by_user_id' => $authority?->guardian_user_id ?? $child->id,
                    'authority_request_id' => $authority?->id,
                    'policy_version' => config('learner_age_policy.adult_dependent_consent_version', 'v1'),
                    'privacy_notice_hash' => $authority?->privacy_notice_hash ?? hash_file('sha256', resource_path('views/legal/privacy.blade.php')),
                    'terms_hash' => $authority?->terms_hash ?? hash_file('sha256', resource_path('views/legal/terms.blade.php')),
                    'accepted_at' => $authority?->authorized_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $child->forceFill([
                'password' => Hash::make($password),
                'minor_account_activation_required' => false,
                'minor_account_activated_at' => now(),
                'minor_privacy_notice_version' => $isMinor ? config('learner_age_policy.guardian_consent_version') : null,
                'minor_privacy_accepted_at' => $isMinor ? now() : null,
            ])->save();
            $profile->forceFill(['username' => $username])->save();
            $this->identityRequirement->createForNewLearner($child->fresh());

            $handoff->forceFill(['used_at' => now()])->save();

            return $child->fresh(['learnerProfile']);
        });
    }

    public function requestPasswordReset(?User $child): void
    {
        if ($child === null) {
            return;
        }

        DB::transaction(function () use ($child): void {
            $lockedChild = User::query()->lockForUpdate()->find($child->id);
            if ($lockedChild === null) {
                return;
            }

            $relationship = ParentChildAccount::query()
                ->where('child_user_id', $lockedChild->id)
                ->minorAccessEligible()
                ->lockForUpdate()
                ->first();
            $guardian = $relationship === null
                ? null
                : User::query()->lockForUpdate()->find($relationship->parent_user_id);

            if ($guardian === null || ! $this->isPasswordRecoveryEligible($relationship, $lockedChild, $guardian)) {
                return;
            }

            MinorAccountHandoff::query()
                ->where('child_user_id', $lockedChild->id)
                ->where('purpose', MinorAccountHandoff::PURPOSE_PASSWORD_RESET)
                ->whereNull('approved_at')
                ->whereNull('used_at')
                ->update(['expires_at' => now()]);

            $handoff = MinorAccountHandoff::query()->create([
                'child_user_id' => $lockedChild->id,
                'guardian_user_id' => $guardian->id,
                'parent_child_account_id' => $relationship->id,
                'purpose' => MinorAccountHandoff::PURPOSE_PASSWORD_RESET,
                'token_hash' => null,
                'approved_at' => null,
                'expires_at' => now()->addMinutes((int) config('learner_age_policy.handoff_minutes')),
            ]);

            DB::afterCommit(function () use ($guardian, $lockedChild, $handoff): void {
                $guardian->notify(new TeenagerPasswordRecoveryRequestedNotification(
                    $lockedChild->fresh(),
                    $handoff->fresh(),
                ));
            });
        });
    }

    /** @return array{0: MinorAccountHandoff, 1: string} */
    public function approvePasswordReset(MinorAccountHandoff $handoff, User $guardian): array
    {
        return DB::transaction(function () use ($handoff, $guardian): array {
            $lockedHandoff = MinorAccountHandoff::query()->lockForUpdate()->find($handoff->id);
            $lockedGuardian = User::query()->lockForUpdate()->find($guardian->id);

            if ($lockedHandoff === null || $lockedGuardian === null
                || $lockedHandoff->purpose !== MinorAccountHandoff::PURPOSE_PASSWORD_RESET
                || $lockedHandoff->approved_at !== null
                || $lockedHandoff->used_at !== null
                || $lockedHandoff->expires_at?->isPast()) {
                throw new InvalidArgumentException('This recovery request is invalid or expired.');
            }

            $relationship = ParentChildAccount::query()->lockForUpdate()->find($lockedHandoff->parent_child_account_id);
            $child = User::query()->lockForUpdate()->find($lockedHandoff->child_user_id);
            if ($relationship === null || $child === null
                || (int) $relationship->parent_user_id !== (int) $lockedGuardian->id
                || (int) $relationship->child_user_id !== (int) $child->id
                || (int) $lockedHandoff->guardian_user_id !== (int) $lockedGuardian->id
                || ! $this->isPasswordRecoveryEligible($relationship, $child, $lockedGuardian)) {
                throw new InvalidArgumentException('This recovery request is invalid or expired.');
            }

            $rawCode = $this->generateCode();
            $lockedHandoff->forceFill([
                'token_hash' => $this->digestForStorage($rawCode),
                'approved_at' => now(),
                'expires_at' => now()->addMinutes((int) config('learner_age_policy.handoff_minutes')),
            ])->save();

            return [$lockedHandoff->fresh(['child', 'guardian', 'relationship']), $rawCode];
        });
    }

    public function consumePasswordReset(string $code, string $username, string $password): User
    {
        return DB::transaction(function () use ($code, $username, $password): User {
            $handoff = MinorAccountHandoff::query()
                ->where('purpose', MinorAccountHandoff::PURPOSE_PASSWORD_RESET)
                ->where('token_hash', $this->digestForStorage($this->normalizeCode($code)))
                ->lockForUpdate()
                ->first();

            if ($handoff === null || $handoff->approved_at === null || $handoff->used_at !== null || $handoff->expires_at?->isPast()) {
                throw new InvalidArgumentException('This recovery code is invalid or expired.');
            }

            $relationship = ParentChildAccount::query()->lockForUpdate()->find($handoff->parent_child_account_id);
            $child = User::query()->lockForUpdate()->find($handoff->child_user_id);
            $guardian = User::query()->lockForUpdate()->find($handoff->guardian_user_id);
            $profile = $child?->learnerProfile()->first();
            $submittedUsername = strtolower(trim($username));

            if ($relationship === null || $child === null || $guardian === null || $profile === null
                || ! hash_equals(strtolower((string) $profile->username), $submittedUsername)
                || (int) $relationship->parent_user_id !== (int) $guardian->id
                || (int) $relationship->child_user_id !== (int) $child->id
                || ! $this->isPasswordRecoveryEligible($relationship, $child, $guardian)) {
                throw new InvalidArgumentException('This recovery code is invalid or expired.');
            }

            $child->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($child));
            $handoff->forceFill(['used_at' => now()])->save();

            return $child->fresh();
        });
    }

    private function assertActivationEligible(ParentChildAccount $relationship, User $child): void
    {
        $isMinor = LearnerAgePolicy::classify($child->birthdate) === LearnerAgePolicy::ELIGIBLE_TEENAGER;
        if (! in_array(LearnerAgePolicy::classify($child->birthdate), [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)
            || ! $child->minor_account_activation_required
            || $relationship->relationship_verified_at === null
            || ($isMinor && ($relationship->minor_registration_authorized_at === null
                || $relationship->minor_registration_authorized_at->lt($relationship->relationship_verified_at)
                || ! DB::table('minor_authorization_records')
                ->where('parent_child_account_id', $relationship->id)
                ->where('guardian_user_id', $relationship->parent_user_id)
                ->where('consent_version', config('learner_age_policy.guardian_consent_version'))
                ->where('accepted_at', '>=', $relationship->relationship_verified_at)
                ->exists()))
            || ! ParentChildAccount::query()->whereKey($relationship->id)->accessEligible()->exists()) {
            throw new InvalidArgumentException('This activation code is invalid or expired.');
        }
    }

    public function approvedAdultAuthority(ParentChildAccount $relationship): ?AdultDependentAuthorityRequest
    {
        if ($relationship->relationship_verified_at === null
            || ! ParentChildAccount::query()->accessEligible()->whereKey($relationship->id)->exists()) {
            return null;
        }
        $authority = $relationship->adultAuthorityRequests()->where('status', 'approved')
            ->where('guardian_user_id', $relationship->parent_user_id)
            ->whereNotNull('reviewed_by')->whereNotNull('reviewed_at')
            ->where('authorized_at', '>=', $relationship->relationship_verified_at)
            ->where('policy_version', config('learner_age_policy.adult_dependent_consent_version'))
            ->latest('id')->first();
        if (! $authority || ! Storage::disk('local')->exists($authority->document_path)) {
            return null;
        }
        $documentHash = hash_file('sha256', Storage::disk('local')->path($authority->document_path));
        $privacyHash = hash_file('sha256', resource_path('views/legal/privacy.blade.php'));
        $termsHash = hash_file('sha256', resource_path('views/legal/terms.blade.php'));

        return $relationship->child !== null
            && hash_equals($authority->dependent_identity_hash, AdultDependentAuthorityRequest::identityHash($relationship->child))
            && is_string($documentHash) && is_string($privacyHash) && is_string($termsHash)
            && hash_equals($authority->document_hash, $documentHash)
            && hash_equals($authority->privacy_notice_hash, $privacyHash)
            && hash_equals($authority->terms_hash, $termsHash) ? $authority : null;
    }

    private function isPasswordRecoveryEligible(
        ?ParentChildAccount $relationship,
        User $child,
        User $guardian,
    ): bool {
        return $child->isLearner()
            && $child->status === User::STATUS_ACTIVE
            && LearnerAgePolicy::classify($child->birthdate) === LearnerAgePolicy::ELIGIBLE_TEENAGER
            && $child->hasCompletedMinorActivation()
            && $guardian->status === User::STATUS_ACTIVE
            && $guardian->hasVerifiedEmail()
            && $guardian->parent_verification_status === 'approved'
            && $guardian->hasCompletedGuardianOnboarding()
            && $relationship !== null
            && (int) $relationship->parent_user_id === (int) $guardian->id
            && (int) $relationship->child_user_id === (int) $child->id
            && ParentChildAccount::query()->whereKey($relationship->id)->minorAccessEligible()->exists();
    }

    public function generateCode(): string
    {
        return strtoupper(Str::random(16));
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $code));
    }

    public function digestForStorage(string $code): string
    {
        return hash_hmac('sha256', $this->normalizeCode($code), (string) config('app.key'));
    }
}
