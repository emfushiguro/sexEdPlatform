<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreLearnerIdentityDocumentRequest;
use App\Http\Requests\Auth\SubmitLearnerIdentitySelfieRequest;
use App\Services\Identity\LearnerIdentityDocumentDraft;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Identity\LearnerIdentitySubmission;
use App\Services\Persona\PersonaInquiryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LearnerIdentityVerificationController extends Controller
{
    public function __construct(
        private LearnerIdentityRequirement $requirement,
        private PersonaInquiryService $personaInquiries,
    ) {}

    public function create(Request $request, LearnerIdentityDocumentDraft $drafts): RedirectResponse|View
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
        $personaRequired = $this->personaInquiries->isRequired();
        $personaAttempt = $personaRequired ? $this->personaInquiries->currentAttempt($request->user()) : null;
        $draft = $personaRequired && ! $personaAttempt?->isPolicyEligible()
            ? null
            : $drafts->current($request->user(), $case);
        if ($personaRequired && ! $personaAttempt?->isPolicyEligible()) {
            $drafts->clear();
        }

        return view('auth.learner-identity-verification', compact(
            'case', 'draft', 'personaAttempt', 'personaRequired',
        ));
    }

    public function storeDocument(StoreLearnerIdentityDocumentRequest $request, LearnerIdentityDocumentDraft $drafts): RedirectResponse
    {
        $case = $this->requirement->current($request->user());
        abort_if($case === null, 403);

        if ($this->personaInquiries->isRequired()) {
            try {
                $this->personaInquiries->assertLearnerMaySubmit(
                    $request->user(),
                    $case,
                    (string) $request->input('document_type'),
                    $request->filled('government_id_type') ? (string) $request->input('government_id_type') : null,
                );
            } catch (DomainException) {
                return redirect()->route('learner.identity.create')
                    ->withErrors(['persona' => 'Complete the Persona evidence step before uploading local evidence.']);
            }
        }

        $drafts->stage(
            $request->user(), $case,
            $request->safe()->except(['identity_front', 'identity_back']),
            $request->file('identity_front'), $request->file('identity_back'),
        );

        return redirect()->route('learner.identity.selfie.create');
    }

    public function selfie(Request $request, LearnerIdentityDocumentDraft $drafts): RedirectResponse|View
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
        $personaRequired = $this->personaInquiries->isRequired();
        $personaAttempt = $personaRequired ? $this->personaInquiries->currentAttempt($request->user()) : null;
        if ($personaRequired && ! $personaAttempt?->isPolicyEligible()) {
            return redirect()->route('learner.identity.create');
        }
        if (! $drafts->current($request->user(), $case)) {
            return redirect()->route('learner.identity.create');
        }

        return view('auth.learner-identity-selfie', compact('case', 'personaAttempt', 'personaRequired'));
    }

    public function store(SubmitLearnerIdentitySelfieRequest $request, LearnerIdentitySubmission $submission, LearnerIdentityDocumentDraft $drafts): RedirectResponse
    {
        $case = $this->requirement->current($request->user());
        abort_if($case === null, 403);
        $draft = $drafts->current($request->user(), $case);
        if (! $draft) {
            return redirect()->route('learner.identity.create');
        }

        $files = $drafts->files($request->user(), $case);
        if ($selfie = $request->file('selfie')) {
            $files['selfie'] = $selfie;
        }
        $submission->submit($request->user(), $case, [
            'document_type' => $draft['document_type'],
            'government_id_type' => $draft['government_id_type'],
            'government_id_type_other' => $draft['government_id_type_other'],
            'confirm_submission' => $request->input('confirm_submission'),
        ], $files);
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => $drafts->clear());
        } else {
            $drafts->clear();
        }

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

        $personaRequired = $this->personaInquiries->isRequired();

        return view('auth.learner-identity-status', [
            'case' => $case,
            'supportHold' => false,
            'personaRequired' => $personaRequired,
            'personaAttempt' => $personaRequired ? $this->personaInquiries->currentAttempt($request->user()) : null,
        ]);
    }
}
