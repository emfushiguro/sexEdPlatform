<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuardianRelationshipVerificationRequest;
use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipCorrectionDraft;
use App\Services\GuardianRelationshipVerificationService;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuardianRelationshipVerificationController extends Controller
{
    public function __construct(
        private readonly GuardianRelationshipVerificationService $service,
        private readonly GuardianRelationshipCorrectionDraft $drafts,
    ) {}

    public function show(Request $request, ParentChildAccount $parentChildAccount): View
    {
        $this->authorizeGuardian($request, $parentChildAccount);

        return view('parent.relationship-verifications.show', [
            'relationship' => $parentChildAccount->load([
                'child.learnerProfile',
                'verificationDocuments' => fn ($query) => $query
                    ->orderByDesc('submission_round')
                    ->orderBy('display_order'),
                'verificationAudits.actor',
            ]),
            'documentTypes' => GuardianRelationshipTypes::documentTypeOptions($parentChildAccount->relationship_type),
            'pathwayLabel' => GuardianRelationshipTypes::pathwayLabel($parentChildAccount->relationship_type),
            'requiredDocumentTypes' => GuardianRelationshipTypes::requiredDocumentTypes($parentChildAccount->relationship_type),
            'requiresCircumstances' => GuardianRelationshipTypes::requiresCircumstances($parentChildAccount->relationship_type),
            'requiresVerification' => $parentChildAccount->requiresRelationshipVerification(),
            'savedDrafts' => collect($this->drafts->current($request->user(), $parentChildAccount))
                ->map(fn (array $saved) => ['name' => $saved['name']])->all(),
        ]);
    }

    public function store(StoreGuardianRelationshipVerificationRequest $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        $this->authorizeGuardian($request, $parentChildAccount);
        abort_unless($parentChildAccount->requiresRelationshipVerification(), 404);

        $validated = $request->validated();

        $submit = fn () => $this->service->submit(
            $parentChildAccount,
            $request->user(),
            $validated['documents'],
            $validated['relationship_notes'] ?? null,
            currentEvidenceRound: (int) $validated['current_evidence_round'],
        );
        if ($parentChildAccount->relationship_correction_request !== null) {
            $this->drafts->submit($request, $request->user(), $parentChildAccount, $submit);
        } else {
            $submit();
        }

        return redirect()->route('parent.relationship-verifications.show', $parentChildAccount)
            ->with('success', 'Relationship verification submitted for admin review.');
    }

    public function storeDraft(Request $request, ParentChildAccount $parentChildAccount): \Illuminate\Http\JsonResponse
    {
        $this->authorizeGuardian($request, $parentChildAccount);
        abort_unless($parentChildAccount->requiresRelationshipVerification(), 404);
        $validated = $request->validate([
            'current_evidence_round' => ['required', 'integer', 'min:0'],
            'slot' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'documents' => ['prohibited'],
        ]);
        $saved = $this->drafts->save($request->user(), $parentChildAccount, (int) $validated['current_evidence_round'], $validated['slot'], $validated['file']);

        return response()->json(['name' => $saved['name']]);
    }

    public function document(Request $request, ParentChildAccount $parentChildAccount, GuardianRelationshipVerificationDocument $document)
    {
        $this->authorizeGuardian($request, $parentChildAccount);
        abort_unless((int) $document->parent_child_account_id === (int) $parentChildAccount->id, 404);

        return $request->boolean('inline')
            ? Storage::disk($document->disk)->response(
                $document->path,
                $document->original_name,
                [],
                'inline',
            )
            : Storage::disk($document->disk)->download(
                $document->path,
                $document->original_name,
            );
    }

    private function authorizeGuardian(Request $request, ParentChildAccount $relationship): void
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && (int) $relationship->parent_user_id === (int) $user->id
                && $user->status === User::STATUS_ACTIVE
                && $user->isParentVerificationApproved(),
            403,
        );
    }
}
