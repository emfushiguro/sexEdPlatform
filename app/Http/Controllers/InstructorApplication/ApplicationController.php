<?php

namespace App\Http\Controllers\InstructorApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstructorApplication\SaveInstructorApplicationDraftRequest;
use App\Http\Requests\SubmitInstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Services\InstructorApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private readonly InstructorApplicationService $service) {}

    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('view', InstructorApplication::class)) {
            return redirect()->route('instructor-application.dashboard')
                ->with('error', 'Complete Stage One identity approval before starting the instructor application.');
        }

        $application = $user->instructorApplications()
            ->whereIn('status', ['draft', 'pending'])
            ->latest('id')
            ->first();

        if ($application?->status === 'pending') {
            return redirect()->route('instructor-application.application.submitted');
        }

        return view('instructor-application.form', [
            'user' => $user->load('profile'),
            'application' => $application,
            'requiresGovernmentId' => ! $user->instructorIdentityVerifications()->current()->accessApproved()->exists(),
        ]);
    }

    public function saveDraft(SaveInstructorApplicationDraftRequest $request): RedirectResponse
    {
        $this->service->saveDraft($request->user(), $request->validated());

        return redirect()->route('instructor-application.application.edit')
            ->with('status', 'Your application draft was saved.');
    }

    public function submit(SubmitInstructorApplicationRequest $request): RedirectResponse
    {
        $this->service->submitApplication($request->user(), $request->validated());

        return redirect()->route('instructor-application.application.submitted')
            ->with('success', 'Your instructor application has been submitted successfully.');
    }

    public function submitted(Request $request): View|RedirectResponse
    {
        $application = $request->user()->instructorApplications()->where('status', 'pending')->latest('id')->first();
        if (! $application) {
            return redirect()->route('instructor-application.application.edit');
        }

        return view('instructor-application.submitted', compact('application'));
    }

    public function withdraw(Request $request): RedirectResponse
    {
        $application = $request->user()->instructorApplications()
            ->whereIn('status', ['draft', 'pending'])
            ->latest('id')
            ->first();

        if (! $application || ! $request->user()->can('withdraw', $application)) {
            return back()->with('error', 'No active instructor application is available to withdraw.');
        }

        $application->delete();

        return redirect()->route('instructor-application.dashboard')
            ->with('status', 'Your instructor application was withdrawn.');
    }
}
