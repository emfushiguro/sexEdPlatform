<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LearnerEligibilityStatusController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user?->isLearner() || $user->isParentRegistration()) {
            return redirect()->route('learner.dashboard');
        }

        $code = match (LearnerAgePolicy::classify($user->birthdate)) {
            LearnerAgePolicy::ELIGIBLE_TEENAGER => $this->teenagerCode($user),
            LearnerAgePolicy::ELIGIBLE_ADULT => $user->hasVerifiedEmail()
                ? 'profile_required'
                : 'adult_email_required',
            LearnerAgePolicy::INELIGIBLE_UNDERAGE => 'underage',
            LearnerAgePolicy::INELIGIBLE_OVERAGE => 'overage',
            default => 'unclassified',
        };

        return $code === 'profile_required'
            ? redirect()->route('profile.complete')
            : view('auth.learner-eligibility-status', compact('code'));
    }

    private function teenagerCode(User $user): string
    {
        if (! $user->hasCompletedMinorActivation()) {
            return 'activation_required';
        }

        if ($user->qualifyingMinorGuardianRelationship() !== null) {
            return 'profile_required';
        }

        return $user->guardianRelationships()
            ->where(function ($query): void {
                $query->where('relationship_status', ParentChildAccount::STATUS_PENDING)
                    ->orWhere('relationship_verified_status', ParentChildAccount::VERIFICATION_UNDER_REVIEW);
            })->exists()
            ? 'relationship_pending'
            : 'guardian_required';
    }
}
