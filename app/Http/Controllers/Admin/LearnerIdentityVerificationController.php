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
            'case' => $case->load([
                'learner',
                'evidence',
                'audits.actor',
                'personaInquiries' => fn ($query) => $query->orderByDesc('attempt_number'),
            ]),
            'reviewer' => $case->reviewed_by ? User::find($case->reviewed_by) : null,
            'verifiedRelationships' => $case->document_type === 'assisted_review'
                ? $case->learner->guardianRelationships()->accessEligible()->with('parent')->get() : collect(),
            'guardianRelationships' => $case->learner->guardianRelationships()->with('parent')->get(),
        ]);
    }

    public function evidence(Request $request, LearnerIdentityVerification $case, string $slot): BinaryFileResponse
    {
        abort_if($case->superseded_at !== null, 404);
        abort_unless(in_array($slot, ['identity_front', 'identity_back', 'selfie'], true), 404);
        $data = $request->validate(['round' => ['sometimes', 'integer', 'min:1']]);
        if (isset($data['round']) && (int) $data['round'] !== (int) $case->submission_round) {
            $snapshot = $case->audits()->where('submission_round', $data['round'])
                ->whereIn('action', ['submitted', 'resubmitted', 'evidence_preserved'])->latest('id')->get()
                ->map(fn ($audit) => collect($audit->correction_request['evidence'] ?? [])->firstWhere('slot', $slot))
                ->first(fn ($evidence) => $evidence !== null);
            abort_unless($snapshot, 404);
            $evidence = (object) $snapshot;
        } else {
            $evidence = $case->evidence()->where('slot', $slot)->firstOrFail();
        }
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
            'assisted_evidence_reviewed' => $case->document_type === 'assisted_review'
                ? ['accepted'] : ['prohibited'],
            'assisted_review_basis' => $case->document_type === 'assisted_review'
                ? ['required', 'in:independent_photo_record,trusted_verifier_attestation'] : ['prohibited'],
            'assisted_issuer_verified' => $case->document_type === 'assisted_review' ? ['accepted'] : ['prohibited'],
            'assisted_issuer_verification' => $case->document_type === 'assisted_review'
                ? ['required', 'string', 'min:20', 'max:2000'] : ['prohibited'],
            'persona_portrait_compared' => $case->sandbox_manual_review || ((config('persona.enabled', false) || $case->usesPersonaSelfie())
                && $case->pathway === 'teen'
                && in_array($case->document_type, ['school_id', 'institution_id'], true))
                ? ['accepted'] : ['sometimes', 'accepted'],
            'sandbox_philippine_id_confirmed' => $case->sandbox_manual_review
                ? ['accepted'] : ['sometimes', 'accepted'],
            'sandbox_id_type_confirmed' => $case->sandbox_manual_review
                ? ['accepted'] : ['sometimes', 'accepted'],
        ]);
        $this->review->approve(
            $request->user(),
            $case,
            (int) $data['submission_round'],
            $request->boolean('persona_portrait_compared'),
            $request->boolean('sandbox_philippine_id_confirmed'),
            $request->boolean('sandbox_id_type_confirmed'),
            $request->boolean('assisted_evidence_reviewed'),
            $request->input('assisted_review_basis'),
            $request->boolean('assisted_issuer_verified'),
            $request->input('assisted_issuer_verification'),
        );

        return redirect()->route('admin.parent-verifications.learners.show', $case)
            ->with('success', 'Learner identity approved.');
    }

    public function reject(Request $request, LearnerIdentityVerification $case): RedirectResponse
    {
        $data = $request->validate([
            'submission_round' => ['required', 'integer', 'min:1'],
            'reason' => ['required', new Enum(LearnerIdentityRejectionReason::class)],
            'requested_corrections' => ['sometimes', 'array', 'min:1'],
            'requested_corrections.*' => ['required', 'string', 'distinct', 'in:identity_front,identity_back,selfie,persona_identity'],
            'correction_note' => ['nullable', 'string', 'max:2000'],
            'allow_resubmission' => ['sometimes', 'boolean'],
        ]);
        $this->review->reject(
            $request->user(),
            $case,
            LearnerIdentityRejectionReason::from($data['reason']),
            (int) $data['submission_round'],
            $data['requested_corrections'] ?? null, $data['correction_note'] ?? null,
            $request->boolean('allow_resubmission', true),
        );

        return redirect()->route('admin.parent-verifications.learners.show', $case)
            ->with('success', 'Learner identity rejected.');
    }
}
