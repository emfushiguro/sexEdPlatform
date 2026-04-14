<?php

namespace App\Http\Middleware;

use App\Models\ParentChildAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isLearner() && $user->isParentRegistration()) {
            if ($user->isParentVerificationPending() || $user->isParentVerificationRejected()) {
                return redirect()->route('parent.verification.status');
            }
        }

        if ($user && $user->isLearner()) {
            $childVerification = ParentChildAccount::query()
                ->where('child_user_id', $user->id)
                ->first();

            if ($childVerification && in_array($childVerification->verification_status, ['pending', 'rejected'], true)) {
                return redirect()->route('child.verification.status');
            }
        }

        // If user is a learner and hasn't completed their profile
        if ($user && $user->isLearner() && !$user->hasCompletedProfile()) {
            // Allow access to profile completion routes
            if (!$request->routeIs('profile.complete') && !$request->routeIs('profile.store')) {
                return redirect()->route('profile.complete')
                    ->with('warning', 'Please complete your profile to access learning modules.');
            }
        }

        return $next($request);
    }
}
