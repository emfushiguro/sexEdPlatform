<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveInstructorIdentityRequest;
use App\Http\Requests\Admin\RejectInstructorIdentityRequest;
use App\Http\Requests\Admin\RequestInstructorIdentityActionRequest;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Policies\InstructorIdentityVerificationPolicy;
use App\Services\InstructorApplication\InstructorIdentityReviewEvidence;
use App\Services\InstructorApplication\InstructorIdentityService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorIdentityVerificationController extends Controller
{
    public function __construct(
        private readonly InstructorIdentityService $identityService,
        private readonly InstructorIdentityReviewEvidence $reviewEvidence,
        private readonly InstructorIdentityVerificationPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $this->assertPermission($request->user());
        $status = $request->string('status')->toString() ?: InstructorIdentityVerification::STATUS_PENDING_REVIEW;
        $allowedStatuses = [
            InstructorIdentityVerification::STATUS_PENDING_REVIEW,
            InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
            InstructorIdentityVerification::STATUS_APPROVED,
            InstructorIdentityVerification::STATUS_REJECTED,
        ];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = InstructorIdentityVerification::STATUS_PENDING_REVIEW;
        }

        $cases = InstructorIdentityVerification::query()
            ->current()
            ->where('status', $status)
            ->with(['user', 'reviewer', 'personaInquiries' => fn ($query) => $query->current()->latest('attempt_number')])
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.instructor-applications.identities.index', [
            'cases' => $cases,
            'status' => $status,
            'pendingCount' => InstructorIdentityVerification::pendingReview()->count(),
            'actionRequiredCount' => InstructorIdentityVerification::query()->current()->where('status', InstructorIdentityVerification::STATUS_ACTION_REQUIRED)->count(),
        ]);
    }

    public function show(Request $request, InstructorIdentityVerification $instructorIdentityVerification): View
    {
        $this->authorizeView($request->user(), $instructorIdentityVerification);
        abort_if($instructorIdentityVerification->superseded_at !== null, 404);

        $case = $instructorIdentityVerification->load([
            'user.profile.city',
            'user.profile.barangay',
            'reviewer',
            'audits.actor',
            'personaInquiries' => fn ($query) => $query->current()->latest('attempt_number'),
        ]);

        return view('admin.instructor-applications.identities.show', [
            'case' => $case,
            'evidence' => $this->reviewEvidence->for($case),
        ]);
    }

    public function approve(ApproveInstructorIdentityRequest $request, InstructorIdentityVerification $instructorIdentityVerification): RedirectResponse
    {
        $data = $request->validated();
        try {
            $this->identityService->approve(
                $instructorIdentityVerification,
                $request->user(),
                [
                    'declared' => true,
                    'provider' => true,
                    'sandbox' => $request->boolean('sandbox_philippine_id_confirmed')
                        && $request->boolean('sandbox_id_type_confirmed')
                        && $request->boolean('sandbox_portraits_compared'),
                ],
                (int) $data['submission_round'],
                (int) $data['persona_inquiry_id'],
                $data['mismatch_rationale'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['review' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.instructor-applications.identities.show', $instructorIdentityVerification)
            ->with('success', 'Instructor identity approved for Stage One.');
    }

    public function reject(RejectInstructorIdentityRequest $request, InstructorIdentityVerification $instructorIdentityVerification): RedirectResponse
    {
        $data = $request->validated();
        try {
            $this->identityService->reject(
                $instructorIdentityVerification,
                $request->user(),
                (string) $data['reason_code'],
                $data['reason_note'] ?? null,
                (int) $data['submission_round'],
                (int) $data['persona_inquiry_id'],
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['review' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.instructor-applications.identities.show', $instructorIdentityVerification)
            ->with('success', 'Instructor identity rejected.');
    }

    public function requestAction(RequestInstructorIdentityActionRequest $request, InstructorIdentityVerification $instructorIdentityVerification): RedirectResponse
    {
        $data = $request->validated();
        try {
            $this->identityService->requestAction(
                $instructorIdentityVerification,
                $request->user(),
                $data['requested_action_codes'],
                $data['reason_note'] ?? null,
                (int) $data['submission_round'],
                (int) $data['persona_inquiry_id'],
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['review' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.instructor-applications.identities.show', $instructorIdentityVerification)
            ->with('success', 'Requested identity follow-up from the applicant.');
    }

    private function authorizeView(?User $user, InstructorIdentityVerification $case): void
    {
        $this->assertPermission($user);
        abort_unless($user !== null && $this->policy->view($user, $case), 403);
    }

    private function assertPermission(?User $user): void
    {
        abort_unless($user !== null && $user->isAdmin() && $user->hasPermissionTo('review instructor identities'), 403);
    }
}
