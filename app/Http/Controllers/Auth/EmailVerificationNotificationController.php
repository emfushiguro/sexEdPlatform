<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthenticatedHomeRouteResolver;
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
