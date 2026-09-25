<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LearnerIdentityRejectionReason;
use App\Http\Controllers\Controller;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Services\Identity\LearnerIdentityReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LearnerIdentityVerificationController extends Controller
{
    public function __construct(private readonly LearnerIdentityReview $review) {}

    public function show(LearnerIdentityVerification $case): View
    {
        abort_if($case->superseded_at !== null, 404);

        return view('admin.parent-verifications.show-learner', [
            'case' => $case->load(['learner', 'evidence', 'audits.actor']),
            'reviewer' => $case->reviewed_by ? User::find($case->reviewed_by) : null,
        ]);
    }

    public function evidence(LearnerIdentityVerification $case, string $slot): BinaryFileResponse
    {
        abort_if($case->superseded_at !== null, 404);
        abort_unless(in_array($slot, ['identity_front', 'identity_back', 'selfie'], true), 404);
        $evidence = $case->evidence()->where('slot', $slot)->firstOrFail();
        abort_unless(in_array($evidence->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true), 404);
        abort_unless(Storage::disk('local')->exists($evidence->storage_path), 404);

        $extension = match ($evidence->mime_type) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        };

        return new BinaryFileResponse(Storage::disk('local')->path($evidence->storage_path), 200, [
            'Content-Type' => $evidence->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="'.$slot.'.'.$extension.'"',
        ], false, null, false, false);
    }

    public function approve(Request $request, LearnerIdentityVerification $case): RedirectResponse
    {
        $data = $request->validate([
            'submission_round' => ['required', 'integer', 'min:1'],
            'confirm_government_issued' => $case->pathway === 'adult' && $case->government_id_type === 'other'
                ? ['accepted'] : ['sometimes', 'accepted'],
        ]);
        $this->review->approve($request->user(), $case, (int) $data['submission_round']);

        return redirect()->route('admin.parent-verifications.learners.show', $case)
            ->with('success', 'Learner identity approved.');
    }

    public function reject(Request $request, LearnerIdentityVerification $case): RedirectResponse
    {
        $data = $request->validate([
            'submission_round' => ['required', 'integer', 'min:1'],
            'reason' => ['required', new Enum(LearnerIdentityRejectionReason::class)],
        ]);
        $this->review->reject(
            $request->user(),
            $case,
            LearnerIdentityRejectionReason::from($data['reason']),
            (int) $data['submission_round'],
        );

        return redirect()->route('admin.parent-verifications.learners.show', $case)
            ->with('success', 'Learner identity rejected.');
    }
}
