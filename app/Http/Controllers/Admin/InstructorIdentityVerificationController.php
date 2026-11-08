<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveInstructorIdentityRequest;
use App\Http\Requests\Admin\RejectInstructorIdentityRequest;
use App\Http\Requests\Admin\RequestInstructorIdentityActionRequest;
use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Policies\InstructorIdentityVerificationPolicy;
use App\Services\InstructorApplication\InstructorIdentityReviewEvidence;
use App\Services\InstructorApplication\InstructorIdentityService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
        $stage = $request->string('stage')->toString() ?: 'all';
        $search = trim($request->string('search')->toString());
        $allowedStatuses = [
            'all',
            InstructorIdentityVerification::STATUS_PENDING_REVIEW,
            InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
            InstructorIdentityVerification::STATUS_APPROVED,
            InstructorIdentityVerification::STATUS_REJECTED,
        ];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = InstructorIdentityVerification::STATUS_PENDING_REVIEW;
        }
        if (! in_array($stage, ['all', 'stage_one', 'stage_two'], true)) {
            $stage = 'all';
        }

        $casesQuery = InstructorIdentityVerification::query()
            ->current()
            ->whereIn('status', [InstructorIdentityVerification::STATUS_PENDING_REVIEW, InstructorIdentityVerification::STATUS_ACTION_REQUIRED, InstructorIdentityVerification::STATUS_APPROVED, InstructorIdentityVerification::STATUS_REJECTED])
            ->with([
                'user.instructorApplications' => fn ($query) => $query
                    ->whereIn('status', ['pending', 'approved', 'rejected'])
                    ->latest('id'),
                'reviewer',
                'personaInquiries' => fn ($query) => $query->current()->latest('attempt_number'),
            ])
            ->when($stage === 'stage_two', fn ($query) => $query->whereHas('user.instructorApplications', fn ($applicationQuery) => $applicationQuery->whereIn('status', ['pending', 'approved', 'rejected'])))
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($userQuery) => $this->applyApplicantSearch($userQuery, $search)))
            ->latest('submitted_at');

        $cases = $casesQuery->get();

        $standaloneApplicationsQuery = InstructorApplication::query()
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->whereDoesntHave('instructorIdentityVerification', fn ($query) => $query->whereNull('superseded_at'))
            ->whereDoesntHave('user.instructorIdentityVerifications', fn ($query) => $query->current())
            ->with(['user', 'latestReview.reviewedBy'])
            ->when($search !== '', fn ($query) => $query->where(function ($applicationQuery) use ($search) {
                $applicationQuery
                    ->where('educational_background', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($userQuery) => $this->applyApplicantSearch($userQuery, $search));
            }))
            ->latest('submitted_at');

        $standaloneApplications = $stage === 'stage_one' ? collect() : $standaloneApplicationsQuery->get();

        $rows = $cases->map(function (InstructorIdentityVerification $case): array {
            $application = $case->user?->instructorApplications?->first();

            return [
                'type' => 'identity',
                'case' => $case,
                'application' => $application,
                'status' => $this->combinedQueueStatus($case->status, $application?->status, ! empty($application?->application_metadata['document_change_request']['slots'])),
                'submitted_at' => $application?->submitted_at ?? $case->submitted_at,
                'stage_label' => $application ? 'Stage One + Stage Two' : 'Stage One',
                'round' => $case->submission_round,
            ];
        })->concat($standaloneApplications->map(fn (InstructorApplication $application): array => [
            'type' => 'application',
            'case' => null,
            'application' => $application,
            'status' => ! empty($application->application_metadata['document_change_request']['slots']) ? InstructorIdentityVerification::STATUS_ACTION_REQUIRED : match ($application->status) {
                'pending' => InstructorIdentityVerification::STATUS_PENDING_REVIEW,
                'approved' => InstructorIdentityVerification::STATUS_APPROVED,
                'rejected' => InstructorIdentityVerification::STATUS_REJECTED,
                default => InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
            },
            'submitted_at' => $application->submitted_at ?? $application->created_at,
            'stage_label' => 'Stage Two',
            'round' => null,
        ]))->filter(fn (array $row): bool => $status === 'all' || $row['status'] === $status)
            ->sortByDesc(fn (array $row) => $row['submitted_at']?->getTimestamp() ?? 0)
            ->values();

        $page = max(1, $request->integer('page', 1));
        $perPage = 20;
        $rows = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $allCases = InstructorIdentityVerification::query()->current()
            ->whereIn('status', [InstructorIdentityVerification::STATUS_PENDING_REVIEW, InstructorIdentityVerification::STATUS_ACTION_REQUIRED, InstructorIdentityVerification::STATUS_APPROVED, InstructorIdentityVerification::STATUS_REJECTED])
            ->with(['user.instructorApplications' => fn ($query) => $query->whereIn('status', ['pending', 'approved', 'rejected'])->latest('id')])->get();
        $allStandalone = InstructorApplication::query()
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->whereDoesntHave('instructorIdentityVerification', fn ($query) => $query->whereNull('superseded_at'))
            ->whereDoesntHave('user.instructorIdentityVerifications', fn ($query) => $query->current())
            ->get();
        $counts = $allCases->map(function ($case) {
            $app = $case->user?->instructorApplications?->first();
            return $this->combinedQueueStatus($case->status, $app?->status, ! empty($app?->application_metadata['document_change_request']['slots']));
        })->concat($allStandalone->map(fn ($app) => ! empty($app->application_metadata['document_change_request']['slots']) ? 'action_required' : ($app->status === 'pending' ? 'pending_review' : $app->status)))->countBy();

        $user = $request->user();

        return view('admin.instructor-applications.identities.index', [
            'rows' => $rows,
            'cases' => $cases,
            'standaloneApplications' => $standaloneApplications,
            'status' => $status,
            'stage' => $stage,
            'search' => $search,
            'statusCounts' => [
                'pending_review' => (int) ($counts['pending_review'] ?? 0),
                'action_required' => (int) ($counts['action_required'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
            ],
            'pendingCount' => (int) ($counts['pending_review'] ?? 0),
            'actionRequiredCount' => (int) ($counts['action_required'] ?? 0),
            'pendingApplicationCount' => 0,
            'canReviewIdentities' => $this->canReviewIdentities($user),
            'canReviewApplications' => $this->canReviewApplications($user),
        ]);
    }

    private function applyApplicantSearch($query, string $search): void
    {
        $like = "%{$search}%";
        $query->where(function ($userQuery) use ($like) {
            $userQuery
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like);
        });
    }

    private function combinedQueueStatus(string $identityStatus, ?string $applicationStatus, bool $documentsRequested = false): string
    {
        if ($identityStatus === InstructorIdentityVerification::STATUS_ACTION_REQUIRED || ($applicationStatus === 'pending' && $documentsRequested)) {
            return InstructorIdentityVerification::STATUS_ACTION_REQUIRED;
        }

        if ($identityStatus === InstructorIdentityVerification::STATUS_PENDING_REVIEW || $applicationStatus === 'pending') {
            return InstructorIdentityVerification::STATUS_PENDING_REVIEW;
        }

        if ($identityStatus === InstructorIdentityVerification::STATUS_REJECTED || $applicationStatus === 'rejected') {
            return InstructorIdentityVerification::STATUS_REJECTED;
        }

        return InstructorIdentityVerification::STATUS_APPROVED;
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

        $application = InstructorApplication::query()
            ->where('instructor_identity_verification_id', $case->id)
            ->where('status', '!=', 'draft')
            ->with(['latestReview.reviewedBy', 'approvedBy'])
            ->latest('id')
            ->first();
        if (! $application) {
            $application = $case->user?->instructorApplications()
                ->where('status', '!=', 'draft')
                ->with(['latestReview.reviewedBy', 'approvedBy'])
                ->latest('id')
                ->first();
        }

        $user = $request->user();
        $canReviewIdentities = $this->canReviewIdentities($user);

        return view('admin.instructor-applications.identities.show', [
            'case' => $case,
            'application' => $application,
            'evidence' => $canReviewIdentities
                ? $this->reviewEvidence->for($case)
                : ['attempt' => null, 'provider_attributes' => [], 'provider_available' => false],
            'canReviewIdentities' => $canReviewIdentities,
            'canReviewApplications' => $this->canReviewApplications($user),
        ]);
    }

    public function showApplication(Request $request, InstructorApplication $application): View|RedirectResponse
    {
        $this->assertPermission($request->user());
        abort_if($application->status === 'draft', 404);

        $application->load(['user', 'latestReview.reviewedBy', 'approvedBy']);
        $case = $this->currentCaseForApplication($application);

        if ($case && $this->canReviewIdentities($request->user())) {
            return redirect()->route('admin.instructor-applications.identities.show', $case);
        }

        if ($case) {
            $case->load([
                'user.profile.city',
                'user.profile.barangay',
                'reviewer',
                'audits.actor',
                'personaInquiries' => fn ($query) => $query->current()->latest('attempt_number'),
            ]);
        }

        $canReviewIdentities = $this->canReviewIdentities($request->user());

        return view('admin.instructor-applications.identities.show', [
            'case' => $case,
            'application' => $application,
            'evidence' => $case && $canReviewIdentities
                ? $this->reviewEvidence->for($case)
                : ['attempt' => null, 'provider_attributes' => [], 'provider_available' => false],
            'canReviewIdentities' => $canReviewIdentities,
            'canReviewApplications' => $this->canReviewApplications($request->user()),
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
        if ($this->canReviewIdentities($user)) {
            abort_unless($this->policy->view($user, $case), 403);
        }
    }

    private function assertPermission(?User $user): void
    {
        abort_unless($user !== null && $user->isAdmin() && (
            $this->canReviewIdentities($user) || $this->canReviewApplications($user)
        ), 403);
    }

    private function canReviewIdentities(?User $user): bool
    {
        return $user !== null && $user->isAdmin() && $user->hasPermissionTo('review instructor identities');
    }

    private function canReviewApplications(?User $user): bool
    {
        return $user !== null && $user->isAdmin() && $user->hasPermissionTo('review instructor applications');
    }

    private function currentCaseForApplication(InstructorApplication $application): ?InstructorIdentityVerification
    {
        $case = $application->instructorIdentityVerification()
            ->whereNull('superseded_at')
            ->first();

        if ($case) {
            return $case;
        }

        return $application->user?->instructorIdentityVerifications()
            ->current()
            ->latest('submission_round')
            ->first();
    }
}
