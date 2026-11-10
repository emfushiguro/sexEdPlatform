<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\ParentChildAccount;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use App\Models\UserSuspension;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final class OnboardingGuideAudienceService
{
    public function canAccessRole(User $user, string $role): bool
    {
        if ($user->status !== User::STATUS_ACTIVE || $this->hasActiveSuspension($user)) {
            return false;
        }

        return match ($role) {
            OnboardingGuide::ROLE_INSTRUCTOR => $user->isInstructor(),
            OnboardingGuide::ROLE_LEARNER => $user->canSwitchToLearnerView()
                || $this->canAccessLearnerDashboard($user),
            default => false,
        };
    }

    public function currentFor(User $user, string $role): ?OnboardingGuideVersion
    {
        if (! $this->canAccessRole($user, $role)) {
            return null;
        }

        $guide = OnboardingGuide::query()
            ->where('guide_key', OnboardingGuide::GUIDE_KEY_DASHBOARD_NAVIGATION)
            ->where('target_role', $role)
            ->where('status', OnboardingGuide::STATUS_PUBLISHED)
            ->with('currentVersion')
            ->latest('id')
            ->first();

        $version = $guide?->currentVersion;
        if (! $guide || ! $version
            || (int) $guide->current_version_id !== (int) $version->id
            || (int) $version->onboarding_guide_id !== (int) $guide->id
            || $version->published_at === null) {
            return null;
        }

        return $version;
    }

    public function presentationFor(User $user, string $role): ?array
    {
        $version = $this->currentFor($user, $role);
        if (! $version) {
            return null;
        }

        $status = $this->statusFor($user, $version);
        if (in_array($status['status'], ['completed', 'dismissed'], true)) {
            return null;
        }

        return [
            'version' => $version,
            'status' => $status,
        ];
    }

    /**
     * @return array{status: string, completed_at: mixed, dismissed_at: mixed, source_version_id: int|null, boundary_version_id: int}
     */
    public function statusFor(User $user, OnboardingGuideVersion $version): array
    {
        $guide = $this->freshGuideFor($version);
        $versions = $guide->versions()
            ->where('version_number', '<=', $version->version_number)
            ->orderBy('version_number')
            ->get();

        $boundary = $versions->filter(
            fn (OnboardingGuideVersion $candidate): bool => $candidate->requires_recompletion
        )->last() ?? $version;

        $effectiveIds = $versions
            ->filter(fn (OnboardingGuideVersion $candidate): bool =>
                $candidate->version_number >= $boundary->version_number
                && $candidate->version_number <= $version->version_number)
            ->pluck('id');

        $states = UserOnboardingGuideState::query()
            ->where('user_id', $user->id)
            ->whereIn('onboarding_guide_version_id', $effectiveIds)
            ->with('version:id,version_number')
            ->get();

        return $this->statePayload($states, (int) $boundary->id);
    }

    public function assertCanUseVersion(User $user, OnboardingGuideVersion $version): void
    {
        $guide = $this->freshGuideFor($version);

        if (! $guide
            || $guide->guide_key !== OnboardingGuide::GUIDE_KEY_DASHBOARD_NAVIGATION
            || $guide->status !== OnboardingGuide::STATUS_PUBLISHED
            || (int) $guide->current_version_id !== (int) $version->id
            || $version->published_at === null
            || ! $this->canAccessRole($user, $guide->target_role)) {
            throw new AuthorizationException('This onboarding guide is not available.');
        }
    }

    /**
     * @return array{eligible: int, completed: int, dismissed: int, incomplete: int, completion_percentage: float}
     */
    public function analyticsFor(OnboardingGuide $guide): array
    {
        $version = $guide->currentVersion;
        if ($guide->status !== OnboardingGuide::STATUS_PUBLISHED || ! $version) {
            return $this->emptyAnalytics();
        }

        $eligible = $this->candidateUsers($guide->target_role)
            ->filter(fn (User $user): bool => $this->canAccessRole($user, $guide->target_role))
            ->values();

        if ($eligible->isEmpty()) {
            return $this->emptyAnalytics();
        }

        $boundary = $this->boundaryFor($version);
        $versionIds = $guide->versions()
            ->whereBetween('version_number', [$boundary->version_number, $version->version_number])
            ->pluck('id');
        $states = UserOnboardingGuideState::query()
            ->whereIn('user_id', $eligible->pluck('id'))
            ->whereIn('onboarding_guide_version_id', $versionIds)
            ->with('version:id,version_number')
            ->get()
            ->groupBy('user_id');

        $completed = 0;
        $dismissed = 0;
        foreach ($eligible as $user) {
            $status = $this->statePayload($states->get($user->id, new Collection()), (int) $boundary->id);
            if ($status['status'] === 'completed') {
                $completed++;
            } elseif ($status['status'] === 'dismissed') {
                $dismissed++;
            }
        }

        $eligibleCount = $eligible->count();

        return [
            'eligible' => $eligibleCount,
            'completed' => $completed,
            'dismissed' => $dismissed,
            'incomplete' => $eligibleCount - $completed,
            'completion_percentage' => $eligibleCount === 0
                ? 0.0
                : round(($completed / $eligibleCount) * 100, 2),
        ];
    }

    private function canAccessLearnerDashboard(User $user): bool
    {
        if (! $user->isLearner()) {
            return false;
        }

        $profile = $user->learnerProfile;
        if (! $profile
            || blank($profile->username)
            || blank($profile->birthdate)
            || blank($profile->city_code)
            || blank($profile->barangay_code)) {
            return false;
        }

        if ($user->isParentRegistration()
            && (! $user->isParentVerificationApproved() || ! $user->hasCompletedGuardianOnboarding())) {
            return false;
        }

        $childVerification = ParentChildAccount::query()
            ->where('child_user_id', $user->id)
            ->whereNotNull('verification_document_path')
            ->latest('id')
            ->first();
        if ($childVerification && in_array($childVerification->verification_status, ['pending', 'rejected'], true)) {
            return false;
        }

        if ($user->isParentRegistration()) {
            return true;
        }

        if (! $user->identityVerifications()->exists()) {
            return true;
        }

        $case = $user->identityVerifications()
            ->whereNull('superseded_at')
            ->latest('submission_round')
            ->first();

        return $case?->status === 'approved'
            && (! $case->sandbox_manual_review || PersonaInquiry::localManualReviewEnabled());
    }

    /** @return Collection<int, User> */
    private function candidateUsers(string $role): Collection
    {
        $roles = $role === OnboardingGuide::ROLE_LEARNER
            ? [OnboardingGuide::ROLE_LEARNER, OnboardingGuide::ROLE_INSTRUCTOR]
            : [OnboardingGuide::ROLE_INSTRUCTOR];

        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->whereIn('role', $roles)
            ->with([
                'learnerProfile',
                'identityVerifications',
                'parentLinks',
                'roleTransitions',
                'userSuspensions',
                'roles',
            ])
            ->get();
    }

    private function hasActiveSuspension(User $user): bool
    {
        $suspensions = $user->relationLoaded('userSuspensions')
            ? $user->userSuspensions
            : UserSuspension::query()->where('user_id', $user->id)->get();

        return $suspensions->contains(function (UserSuspension $suspension): bool {
            return $suspension->status === 'active'
                && ($suspension->ends_at === null || $suspension->ends_at->isFuture());
        });
    }

    private function boundaryFor(OnboardingGuideVersion $version): OnboardingGuideVersion
    {
        return $this->freshGuideFor($version)->versions()
            ->where('version_number', '<=', $version->version_number)
            ->where('requires_recompletion', true)
            ->latest('version_number')
            ->first() ?? $version;
    }

    private function freshGuideFor(OnboardingGuideVersion $version): OnboardingGuide
    {
        return OnboardingGuide::query()->findOrFail($version->onboarding_guide_id);
    }

    private function statePayload(Collection $states, int $boundaryVersionId): array
    {
        $ordered = $states->sortByDesc(fn (UserOnboardingGuideState $state): int =>
            (int) ($state->version?->version_number ?? 0));
        $completed = $ordered->first(fn (UserOnboardingGuideState $state): bool => $state->completed_at !== null);
        $selected = $completed ?? $ordered->first(fn (UserOnboardingGuideState $state): bool => $state->dismissed_at !== null);

        if (! $selected) {
            return [
                'status' => 'incomplete',
                'completed_at' => null,
                'dismissed_at' => null,
                'source_version_id' => null,
                'boundary_version_id' => $boundaryVersionId,
            ];
        }

        return [
            'status' => $completed ? 'completed' : 'dismissed',
            'completed_at' => $selected->completed_at,
            'dismissed_at' => $selected->dismissed_at,
            'source_version_id' => $selected->onboarding_guide_version_id,
            'boundary_version_id' => $boundaryVersionId,
        ];
    }

    private function emptyAnalytics(): array
    {
        return [
            'eligible' => 0,
            'completed' => 0,
            'dismissed' => 0,
            'incomplete' => 0,
            'completion_percentage' => 0.0,
        ];
    }
}
