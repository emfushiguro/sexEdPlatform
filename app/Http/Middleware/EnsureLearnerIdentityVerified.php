<?php

namespace App\Http\Middleware;

use App\Enums\VerificationStatus;
use App\Models\PersonaInquiry;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Support\LearnerAgePolicy;
use Closure;
use DomainException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLearnerIdentityVerified
{
    public function __construct(private LearnerIdentityRequirement $requirement) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isLearner() || $user->isParentRegistration()) {
            return $next($request);
        }

        $identityRoute = $request->routeIs('learner.identity.*', 'persona.identity.*');
        $safeRoute = $request->routeIs(
            'verification.*', 'minor.registration.*', 'minor.activation.*', 'learner.eligibility.status',
            'logout', 'instructor.logout', 'admin.logout', 'password.*',
            'profile.edit', 'profile.update', 'profile.complete', 'profile.store', 'profile.learner.*',
            'profile.password.update', 'profile.account.delete', 'privacy', 'terms', 'help.*', 'feedback.*',
            'moderation.suspension-status', 'moderation.appeals.*', 'child.verification.*',
            'instructor-application.*',
        );

        if (! $identityRoute && $safeRoute) {
            return $next($request);
        }

        $classification = LearnerAgePolicy::classify($user->birthdate);
        if (! in_array($classification, [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)) {
            return $this->deny($request, 'learner.eligibility.status');
        }

        if ($classification === LearnerAgePolicy::ELIGIBLE_TEENAGER
            && (! $user->hasCompletedMinorActivation() || $user->qualifyingMinorGuardianRelationship() === null)) {
            return $this->deny($request, 'learner.eligibility.status');
        }

        if ($classification === LearnerAgePolicy::ELIGIBLE_ADULT && ! $user->hasVerifiedEmail()) {
            return $this->deny($request, 'verification.notice');
        }

        if ($identityRoute) {
            return $next($request);
        }

        try {
            $case = $this->requirement->current($user);
        } catch (DomainException) {
            return $this->deny($request, 'learner.identity.status');
        }

        if ($case === null) {
            return $this->deny($request, 'learner.identity.create');
        }

        if ($case->status === VerificationStatus::Approved->value
            && (! $case->sandbox_manual_review || PersonaInquiry::localManualReviewEnabled())) {
            return $next($request);
        }

        return $this->deny($request, $case->status === null
            ? 'learner.identity.create' : 'learner.identity.status');
    }

    private function deny(Request $request, string $route): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'code' => 'completion_required',
                'message' => 'Complete the required account steps before continuing.',
            ], 403);
        }

        return redirect()->route($route);
    }
}
