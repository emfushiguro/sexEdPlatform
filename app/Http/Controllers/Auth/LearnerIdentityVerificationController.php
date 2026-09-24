<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SubmitLearnerIdentityRequest;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Identity\LearnerIdentitySubmission;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnerIdentityVerificationController extends Controller
{
    public function __construct(private LearnerIdentityRequirement $requirement) {}

    public function create(Request $request): RedirectResponse|View
    {
        try {
            $case = $this->requirement->current($request->user());
        } catch (DomainException) {
            return redirect()->route('learner.identity.status');
        }
        abort_if($case === null, 403);
        if ($case->status === 'approved') {
            return redirect()->route('profile.complete');
        }
        if ($case->status === 'pending') {
            return redirect()->route('learner.identity.status');
        }
        return view('auth.learner-identity-verification', compact('case'));
    }

    public function store(SubmitLearnerIdentityRequest $request, LearnerIdentitySubmission $submission): RedirectResponse
    {
        $case = $this->requirement->current($request->user());
        if ($case === null) {
            abort(403);
        }
        $submission->submit($request->user(), $case, $request->safe()->except(['identity_front', 'identity_back', 'selfie']),
            $request->only(['identity_front', 'identity_back', 'selfie']));

        return redirect()->route('learner.identity.status');
    }

    public function status(Request $request): RedirectResponse|View
    {
        try {
            $case = $this->requirement->current($request->user());
        } catch (DomainException) {
            return view('auth.learner-identity-status', ['case' => null, 'supportHold' => true]);
        }
        abort_if($case === null, 403);
        if ($case->status === null) {
            return redirect()->route('learner.identity.create');
        }
        return view('auth.learner-identity-status', ['case' => $case, 'supportHold' => false]);
    }
}
