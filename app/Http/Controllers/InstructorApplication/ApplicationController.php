<?php

namespace App\Http\Controllers\InstructorApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstructorApplication\SaveInstructorApplicationDocumentsRequest;
use App\Http\Requests\InstructorApplication\SaveInstructorApplicationDraftRequest;
use App\Http\Requests\SubmitInstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Models\User;
use App\Services\InstructorApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use App\Services\InstructorApplication\InstructorApplicationDocumentStorage;

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

    public function saveDocuments(SaveInstructorApplicationDocumentsRequest $request): RedirectResponse
    {
        if ($request->user()->instructorApplications()->where('status', 'pending')->exists()) {
            return redirect()->route('instructor-application.application.submitted');
        }

        $this->service->saveDraft($request->user(), $request->validated());

        return redirect()->route('instructor-application.application.expertise')
            ->with('status', 'Required documents saved. Continue with your teaching expertise.');
    }

    public function expertise(Request $request): View|RedirectResponse
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

        if (! $this->hasRequiredDocuments($user, $application)) {
            return redirect()->route('instructor-application.application.edit')
                ->with('status', 'Save your required documents before continuing.');
        }

        return view('instructor-application.expertise', [
            'user' => $user->load('profile'),
            'application' => $application,
        ]);
    }

    public function saveExpertiseDraft(SaveInstructorApplicationDraftRequest $request): RedirectResponse
    {
        if ($request->user()->instructorApplications()->where('status', 'pending')->exists()) {
            return redirect()->route('instructor-application.application.submitted');
        }

        $application = $request->user()->instructorApplications()
            ->where('status', 'draft')
            ->latest('id')
            ->first();
        if (! $this->hasRequiredDocuments($request->user(), $application)) {
            return redirect()->route('instructor-application.application.edit')
                ->with('status', 'Save your required documents before continuing.');
        }

        $this->service->saveDraft($request->user(), $request->validated());

        return redirect()->route('instructor-application.application.expertise')
            ->with('status', 'Your teaching expertise draft was saved.');
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

    public function requestedDocuments(Request $request, InstructorApplication $application): View
    {
        abort_unless((int) $application->user_id === (int) $request->user()->id, 403);
        abort_unless($application->status === 'pending' && ! empty($application->application_metadata['document_change_request']['slots']), 404);

        return view('instructor-application.requested-documents', compact('application'));
    }

    public function replaceDocuments(Request $request, InstructorApplication $application, InstructorApplicationDocumentStorage $documents): RedirectResponse
    {
        abort_unless((int) $application->user_id === (int) $request->user()->id, 403);
        $slots = $application->application_metadata['document_change_request']['slots'] ?? [];
        abort_if($application->status !== 'pending' || empty($slots), 404);
        $rules = [];
        foreach ($slots as $slot) {
            $documents->columnFor($slot);
            $rules[$slot] = ['required', 'file', $slot === 'cv_resume' ? 'mimes:pdf,doc,docx' : 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        }
        $data = $request->validate($rules);
        $requestToken = $request->validate(['request_token' => ['required', 'uuid']])['request_token'];

        $saved = DB::transaction(function () use ($application, $request, $documents, $slots, $data, $requestToken): bool {
            $locked = InstructorApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $metadata = $locked->application_metadata ?? [];
            if ($locked->status !== 'pending' || ($metadata['document_change_request']['token'] ?? null) !== $requestToken || ($metadata['document_change_request']['slots'] ?? []) !== $slots) {
                return false;
            }
            $paths = [];
            foreach ($slots as $slot) {
                $stored = $documents->store($request->user(), $data[$slot], $slot);
                $paths[$documents->columnFor($slot)] = $stored['path'];
                $metadata['documents'][$slot] = $stored;
            }
            $metadata['document_change_history'][] = array_merge($metadata['document_change_request'], ['resubmitted_at' => now()->toIso8601String()]);
            unset($metadata['document_change_request']);
            $locked->update(array_merge($paths, ['application_metadata' => $metadata, 'submitted_at' => now()]));
            DB::afterCommit(function () use ($locked): void {
                User::role('admin')->get()->each(function (User $admin) use ($locked): void {
                    try {
                        $admin->notify(new \App\Notifications\InstructorApplicationSubmitted($locked));
                    } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $exception) {
                        report($exception);
                    }
                });
            });

            return true;
        });

        return redirect()->route($request->user()->isLearner() ? 'learner.instructor.submitted' : 'instructor-application.dashboard')->with($saved ? 'status' : 'error', $saved
            ? 'Your replacement documents were submitted for review.'
            : 'The request has changed. Please review your application again.');
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

    private function hasRequiredDocuments(User $user, ?InstructorApplication $application): bool
    {
        if (! $application || ! filled($application->clearance_path) || ! filled($application->cv_resume_path)) {
            return false;
        }

        return $user->instructorIdentityVerifications()->current()->accessApproved()->exists()
            || filled($application->government_id_path);
    }
}
