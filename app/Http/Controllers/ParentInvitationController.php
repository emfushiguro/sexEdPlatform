<?php

namespace App\Http\Controllers;

use App\Http\Requests\Parent\RespondParentChildInvitationRequest;
use App\Http\Requests\Parent\SendParentChildInvitationRequest;
use App\Models\ParentChildInvitation;
use App\Models\User;
use App\Services\ParentChildInvitationService;
use App\Services\Chat\GuardianInvitationConversationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ParentInvitationController extends Controller
{
    public function __construct(
        private readonly ParentChildInvitationService $invitationService,
        private readonly GuardianInvitationConversationService $conversationService,
    )
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $parent = $request->user();

        if ($redirect = $this->ensureApprovedParent($parent)) {
            return $redirect;
        }

        $outgoingInvitations = $this->invitationService->getOutgoingInvitations($parent);

        return view('parent.invitations.index', [
            'outgoingInvitations' => $outgoingInvitations->take(5)->values(),
            'totalOutgoingInvitations' => $outgoingInvitations->count(),
        ]);
    }

    public function conversation(Request $request, ParentChildInvitation $invitation): RedirectResponse
    {
        try {
            $conversation = $this->conversationService->createOrGet($request->user(), $invitation);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['conversation' => $exception->getMessage()]);
        }

        return redirect()->route('chat.conversation.open', $conversation);
    }

    public function history(Request $request): View|RedirectResponse
    {
        $parent = $request->user();

        if ($redirect = $this->ensureApprovedParent($parent)) {
            return $redirect;
        }

        return view('parent.invitations.history', [
            'outgoingInvitations' => $this->invitationService->getOutgoingInvitations($parent),
        ]);
    }

    public function store(SendParentChildInvitationRequest $request): RedirectResponse
    {
        $parent = $request->user();

        if ($redirect = $this->ensureApprovedParent($parent)) {
            return $redirect;
        }

        try {
            $documents = collect($request->validated('documents'))
                ->values()
                ->map(static fn (array $document, int $index): array => [
                    'document_type' => (string) $document['document_type'],
                    'document_side' => (string) $document['document_side'],
                    'pairing_key' => filled($document['pairing_key'] ?? null)
                        ? (string) $document['pairing_key']
                        : null,
                    'display_order' => $index,
                    'file' => $document['file'],
                ])
                ->all();

            $this->invitationService->sendInvitation(
                $parent,
                (string) $request->string('identifier'),
                (string) $request->string('relationship_type'),
                $request->filled('relationship_custom') ? (string) $request->string('relationship_custom') : null,
                $request->filled('message') ? (string) $request->string('message') : null,
                [
                    'documents' => $documents,
                ],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['identifier' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('parent.invitations.index')
            ->with('success', 'Invitation sent successfully.');
    }

    public function show(Request $request, ParentChildInvitation $invitation): View
    {
        $viewer = $request->user();
        $isParentViewer = (int) $viewer->id === (int) $invitation->inviter_parent_user_id;
        $isChildViewer = (int) $viewer->id === (int) $invitation->child_user_id;

        abort_if(! $isParentViewer && ! $isChildViewer, 403);

        $invitation->load([
            'inviterParent:id,name,status,parent_verification_status,created_at',
            'inviterParent.learnerProfile:id,user_id,avatar_path',
            'child:id,name,status',
            'child.learnerProfile:id,user_id,username,avatar_path',
            'parentChildAccount:id,parent_user_id,child_user_id,relationship_status,relationship_verified_status',
            'conversation:id,parent_child_invitation_id,status',
        ]);

        $guardianSummary = [
            'name' => (string) ($invitation->inviterParent?->name ?: 'Guardian'),
            'avatar_path' => $invitation->inviterParent?->learnerProfile?->avatar_path,
            'identity_verified' => $invitation->inviterParent?->parent_verification_status === 'approved',
            'member_since' => $invitation->inviterParent?->created_at?->format('F Y'),
        ];

        $learnerSummary = [
            'name' => (string) ($invitation->child?->name ?: 'Learner'),
            'avatar_path' => $invitation->child?->learnerProfile?->avatar_path,
            'username' => $invitation->child?->learnerProfile?->username,
        ];

        return view('parent.invitations.show', [
            'invitation' => $invitation,
            'isParentViewer' => $isParentViewer,
            'isChildViewer' => $isChildViewer,
            'guardianSummary' => $guardianSummary,
            'learnerSummary' => $learnerSummary,
        ]);
    }

    public function respond(RespondParentChildInvitationRequest $request, ParentChildInvitation $invitation): RedirectResponse
    {
        try {
            $updatedInvitation = $this->invitationService->respondToInvitation(
                $request->user(),
                $invitation,
                (string) $request->string('decision'),
                $request->filled('note') ? (string) $request->string('note') : null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['decision' => $exception->getMessage()]);
        }

        $message = $updatedInvitation->status->value === 'accepted'
            ? 'Invitation accepted. Guardian relationship is awaiting any required admin review.'
            : 'Invitation rejected.';

        return redirect()->route('parent.invitations.show', $updatedInvitation)
            ->with('success', $message);
    }

    public function cancel(Request $request, ParentChildInvitation $invitation): RedirectResponse
    {
        try {
            $this->invitationService->cancelInvitation($request->user(), $invitation);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['invitation' => $exception->getMessage()]);
        }

        return redirect()->route('parent.invitations.index')
            ->with('success', 'Invitation cancelled.');
    }

    private function ensureApprovedParent(?User $parent): ?RedirectResponse
    {
        if (! $parent || ! $parent->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('error', 'Please verify your email first.');
        }

        if (! $parent->canBeParent()) {
            abort(403, 'You must be 18 or older to invite a child account.');
        }

        if (! $parent->isParentRegistration() || ! $parent->isParentVerificationApproved()) {
            return redirect()->route('parent.verification.status')
                ->with('warning', 'Your guardian account is still under admin review.');
        }

        if (! $parent->hasCompletedProfile()) {
            return redirect()->route('profile.complete')
                ->with('warning', 'Please complete your profile before sending invitations.');
        }

        return null;
    }
}
