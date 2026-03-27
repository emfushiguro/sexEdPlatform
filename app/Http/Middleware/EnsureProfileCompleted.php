<?php

namespace App\Http\Middleware;

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
