<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuardianRelationshipVerificationRequest;
use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipVerificationService;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GuardianRelationshipVerificationController extends Controller
{
    public function __construct(private readonly GuardianRelationshipVerificationService $service)
    {
    }

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
        ]);
    }

    public function store(StoreGuardianRelationshipVerificationRequest $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        $this->authorizeGuardian($request, $parentChildAccount);
        abort_unless($parentChildAccount->requiresRelationshipVerification(), 404);

        $validated = $request->validated();

        $this->service->submit(
            $parentChildAccount,
            $request->user(),
            $validated['documents'],
            $validated['relationship_notes'] ?? null,
        );

        return redirect()->route('parent.relationship-verifications.show', $parentChildAccount)
            ->with('success', 'Relationship verification submitted for admin review.');
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
                && $user->parent_verification_status === 'approved',
            403,
        );
    }
}
