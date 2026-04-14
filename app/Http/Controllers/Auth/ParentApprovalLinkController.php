<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParentApprovalLinkController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        $user = User::findOrFail((int) $request->route('id'));

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($currentUser->getAuthIdentifier() !== $user->getAuthIdentifier()) {
            return redirect()->route('parent.verification.status')->with(
                'error',
                'Please sign in with the account associated with this approval link.'
            );
        }

        if (! $user->is_parent_registration || $user->parent_verification_status !== 'approved') {
            return redirect()->route('login')
                ->with('error', 'This approval link is no longer valid.');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('info', 'Please verify your email first before continuing.');
        }

        if (! $user->hasCompletedProfile()) {
            return redirect()->route('profile.complete')
                ->with('success', 'Your parent verification was approved. Complete your profile to continue.');
        }

        return redirect()->route('learner.dashboard')
            ->with('success', 'Your parent verification is approved.')
            ->with('show_parent_approved_dashboard_modal', true);
    }
}
