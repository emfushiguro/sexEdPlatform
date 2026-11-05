<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitInstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Services\InstructorApplicationService;
use App\Services\InstructorApplication\InstructorIdentityService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorApplicationController extends Controller
{
    public function __construct(
        private readonly InstructorApplicationService $service,
        private readonly InstructorIdentityService $identityService,
    )
    {
    }

    public function showForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isLearner()) {
            return redirect()->route('learner.dashboard')
                ->with('error', 'Only learners can submit instructor applications.');
        }

        if ($user->isParentRegistration()) {
            return redirect()->route('learner.dashboard')
                ->with('error', 'Guardian-registration accounts cannot apply as instructors in this workflow.');
        }

        $pending = $user->instructorApplications()->where('status', 'pending')->exists();
        if ($pending) {
            return redirect()->route('learner.instructor.submitted')
                ->with('info', 'You already have a pending instructor application.');
        }

        if (config('instructor_application.identity_enforced_from')) {
            try {
                $this->identityService->satisfyFromApprovedLearnerIdentity($user);
            } catch (DomainException $exception) {
                return redirect()->route('learner.dashboard')
                    ->with('error', $exception->getMessage());
            }
        }

        if (! $user->can('view', InstructorApplication::class)) {
            return redirect()->route('learner.dashboard')
                ->with('error', 'Complete your approved learner identity verification before applying as an instructor.');
        }

        return view('learner.instructor-application.form');
    }

    public function submit(SubmitInstructorApplicationRequest $request): RedirectResponse
    {
        $this->service->submitApplication($request->user(), $request->validated());

        return redirect()->route('learner.instructor.submitted')
            ->with('success', 'Your instructor application has been submitted successfully.');
    }

    public function submitted(): View
    {
        return view('learner.instructor-application.submitted');
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $application = $request->user()->instructorApplications()->where('status', 'pending')->first();

        if ($application) {
            // Delete associated files if needed, currently service doesn't have a delete method exposing this,
            // but we can just delete the record and let a scheduled job clean up files or do it manually.
            // For now, simpler to just delete the record.
            $application->delete();
            return redirect()->route('learner.dashboard')->with('status', 'Application withdrawn successfully.');
        }

        return back()->with('error', 'No pending application found to withdraw.');
    }
}
