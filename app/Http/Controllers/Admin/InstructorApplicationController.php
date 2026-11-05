<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveInstructorApplicationRequest;
use App\Http\Requests\RejectInstructorApplicationRequest;
use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Services\InstructorApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\InstructorApplication\InstructorApplicationDocumentStorage;
use App\Notifications\InstructorApplicationDocumentsRequested;
use DomainException;

class InstructorApplicationController extends Controller
{
    public function __construct(private readonly InstructorApplicationService $service)
    {
    }

    public function index(Request $request): RedirectResponse
    {
        $focusApplicationId = $request->integer('focus');
        if ($focusApplicationId > 0) {
            $application = InstructorApplication::query()->find($focusApplicationId);
            if ($application) {
                return redirect()->route('admin.instructor-applications.review', $application);
            }
        }

        return redirect()->route('admin.instructor-applications.identities.index', array_filter([
            'status' => $request->string('status')->toString() ?: null,
            'stage' => $request->string('stage')->toString() ?: null,
            'search' => trim((string) $request->string('search')) ?: null,
        ]));
    }

    public function show(Request $request, InstructorApplication $application): View|RedirectResponse
    {
        $this->assertStageTwoPermission($request);

        return app(InstructorIdentityVerificationController::class)->showApplication($request, $application);
    }

    public function approve(ApproveInstructorApplicationRequest $request, InstructorApplication $application): RedirectResponse
    {
        try {
            $this->service->approve($application, (string) $request->string('admin_message'), $request->user());
        } catch (DomainException $exception) {
            return redirect()->back()->withErrors(['application' => $exception->getMessage()])->withInput();
        }

        return $this->redirectAfterReview($request, $application)
            ->with('success', 'Instructor application approved successfully.');
    }

    public function requestDocuments(Request $request, InstructorApplication $application, InstructorApplicationDocumentStorage $documents): RedirectResponse
    {
        $this->assertStageTwoPermission($request);
        $data = $request->validate([
            'slots' => ['required', 'array', 'min:1'],
            'slots.*' => ['required', 'distinct', Rule::in(['government_id', 'clearance', 'cv_resume', 'teaching_credential', 'sexed_certificate', 'professional_license'])],
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $saved = DB::transaction(function () use ($application, $documents, $data, $request): bool {
            $locked = InstructorApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending' || ! empty($locked->application_metadata['document_change_request']['slots'])) {
                return false;
            }
            foreach ($data['slots'] as $slot) {
                if (! filled($locked->{$documents->columnFor($slot)})) {
                    return false;
                }
            }
            $metadata = $locked->application_metadata ?? [];
            $metadata['document_change_request'] = [
                'slots' => array_values($data['slots']),
                'note' => trim($data['note']),
                'requested_at' => now()->toIso8601String(),
                'token' => (string) \Illuminate\Support\Str::uuid(),
                'requested_by' => $request->user()->id,
            ];
            $locked->update(['application_metadata' => $metadata]);
            DB::afterCommit(function () use ($locked): void {
                try {
                    $locked->user?->notify(new InstructorApplicationDocumentsRequested($locked));
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $exception) {
                    report($exception);
                }
            });

            return true;
        });

        return back()->with($saved ? 'success' : 'error', $saved
            ? 'Document replacements requested from the applicant.'
            : 'The application changed or the selected documents are unavailable. Reload and try again.');
    }

    public function reject(RejectInstructorApplicationRequest $request, InstructorApplication $application): RedirectResponse
    {
        try {
            $this->service->reject(
                $application,
                (string) $request->string('rejection_reason_code'),
                $request->filled('rejection_reason_note') ? (string) $request->string('rejection_reason_note') : null,
                (string) $request->string('admin_message'),
                $request->user(),
            );
        } catch (DomainException $exception) {
            return redirect()->back()->withErrors(['application' => $exception->getMessage()])->withInput();
        }

        return $this->redirectAfterReview($request, $application)
            ->with('success', 'Instructor application rejected successfully.');
    }

    public function archive(InstructorApplication $application): RedirectResponse
    {
        if ($application->trashed()) {
            return redirect()->back()->with('info', 'Instructor application is already archived.');
        }

        $application->delete();

        return redirect()->back()->with('success', 'Instructor application archived successfully.');
    }

    public function destroy(InstructorApplication $application): RedirectResponse
    {
        if (! in_array($application->status, ['rejected', 'approved'], true)) {
            return redirect()->back()->with('error', 'Only reviewed applications can be permanently deleted.');
        }

        $application->forceDelete();

        return redirect()->back()->with('success', 'Instructor application permanently deleted.');
    }

    private function redirectAfterReview(Request $request, InstructorApplication $application): RedirectResponse
    {
        $identityCaseId = $request->integer('identity_case_id');
        if ($identityCaseId > 0 && InstructorIdentityVerification::query()
            ->current()
            ->whereKey($identityCaseId)
            ->where('user_id', $application->user_id)
            ->exists()) {
            return redirect()->route('admin.instructor-applications.identities.show', $identityCaseId);
        }

        return redirect()->back();
    }

    private function assertStageTwoPermission(Request $request): void
    {
        abort_unless($request->user()?->isAdmin() && $request->user()->hasPermissionTo('review instructor applications'), 403);
    }
}
