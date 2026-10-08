<?php

namespace App\Http\Middleware;

use App\Enums\VerificationStatus;
use App\Services\Identity\LearnerIdentityRequirement;
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
        if (! $user || ! $user->isLearner() || $user->isParentRegistration()
            || $request->routeIs(
                'verification.*', 'learner.identity.*', 'logout', 'instructor.logout', 'admin.logout',
                'password.*', 'profile.password.update', 'profile.account.delete',
                'privacy', 'terms', 'moderation.suspension-status', 'moderation.appeals.*',
            )) {
            return $next($request);
        }
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        try {
            $case = $this->requirement->current($user);
        } catch (DomainException) {
            return redirect()->route('learner.identity.status');
        }
        if ($case === null || $case->status === VerificationStatus::Approved->value) {
            return $next($request);
        }
        return redirect()->route($case->status === null
            ? 'learner.identity.create' : 'learner.identity.status');
    }
}
