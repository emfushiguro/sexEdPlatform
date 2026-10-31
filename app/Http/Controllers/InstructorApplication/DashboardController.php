<?php

namespace App\Http\Controllers\InstructorApplication;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\InstructorApplication\InstructorIdentityService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        InstructorIdentityService $identityService,
        LearnerIdentityRequirement $learnerIdentityRequirement,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $case = null;
        $learnerIdentity = null;
        $identityMessage = null;

        if ($user->isInstructorApplicant()) {
            try {
                $case = $identityService->ensureAwaitingVerification($user);
            } catch (DomainException $exception) {
                $identityMessage = $exception->getMessage();
            }
        } elseif ($user->isLearner()) {
            $learnerIdentity = $learnerIdentityRequirement->current($user);
            if ($learnerIdentity?->status === 'approved') {
                try {
                    $case = $identityService->satisfyFromApprovedLearnerIdentity($user);
                } catch (DomainException $exception) {
                    $identityMessage = $exception->getMessage();
                }
            } else {
                $identityMessage = 'Complete learner identity verification before starting an instructor application.';
            }
        }

        return view('instructor-application.dashboard', [
            'user' => $user,
            'case' => $case,
            'attempt' => $case?->personaInquiries()->current()->latest('attempt_number')->first(),
            'learnerIdentity' => $learnerIdentity,
            'identityMessage' => $identityMessage,
            'application' => $user->instructorApplications()->latest()->first(),
        ]);
    }
}
