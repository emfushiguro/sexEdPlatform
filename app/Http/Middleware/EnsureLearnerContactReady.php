<?php

namespace App\Http\Middleware;

use App\Support\LearnerAgePolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLearnerContactReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'login');
        }

        if (! $user->hasRole('learner')) {
            return $user->hasVerifiedEmail()
                ? $next($request)
                : $this->deny($request, 'verification.notice');
        }

        return match (LearnerAgePolicy::classify($user->birthdate)) {
            LearnerAgePolicy::ELIGIBLE_TEENAGER => $user->hasCompletedMinorActivation()
                && $user->qualifyingMinorGuardianRelationship() !== null
                ? $next($request)
                : $this->deny($request, 'learner.eligibility.status'),
            LearnerAgePolicy::ELIGIBLE_ADULT => $user->hasVerifiedEmail()
                ? $next($request)
                : $this->deny($request, 'verification.notice'),
            default => $this->deny($request, 'learner.eligibility.status'),
        };
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
