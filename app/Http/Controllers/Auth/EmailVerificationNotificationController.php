<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthenticatedHomeRouteResolver;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request, AuthenticatedHomeRouteResolver $homeRouteResolver): RedirectResponse
    {
        if ($request->user()->email === null) {
            if ($request->user()->isLearner()
                && LearnerAgePolicy::classify($request->user()->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT) {
                return redirect()->route('verification.notice');
            }

            return redirect()->route('profile.edit')
                ->with('warning', 'Add your own email address before requesting verification.');
        }

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route(
                $homeRouteResolver->routeName($request->user()),
                absolute: false,
            ));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
