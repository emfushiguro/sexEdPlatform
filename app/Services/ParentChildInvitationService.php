<?php

namespace App\Services;

use App\Enums\ParentChildInvitationStatus;
use App\Models\ParentChildAccount;
use App\Models\ParentChildInvitation;
use App\Models\User;
use App\Notifications\Learner\ParentChildInvitationReceivedNotification;
use App\Notifications\Parent\ParentChildInvitationRespondedNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ParentChildInvitationService
{
    public function sendInvitation(User $parent, string $identifier, ?string $message = null): ParentChildInvitation
    {
        $child = $this->resolveChildFromIdentifier($identifier);

        if (! $child || ! $child->isLearner()) {
            throw new InvalidArgumentException('No learner account matches that username or email.');
        }

        if ((int) $parent->id === (int) $child->id) {
            throw new InvalidArgumentException('You cannot invite your own account.');
        }

        $age = $this->resolveChildAge($child);
        if ($age === null || $age < 5 || $age > 17) {
            throw new InvalidArgumentException('Only learners aged 5 to 17 can receive a parent-link invitation.');
        }

        $existingRelationship = ParentChildAccount::withTrashed()
            ->where('parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->first();

        if ($existingRelationship && $existingRelationship->deleted_at === null && $existingRelationship->verification_status === 'approved') {
            throw new InvalidArgumentException('This learner is already linked to your parent account.');
        }

        if ($existingRelationship && $existingRelationship->deleted_at === null && $existingRelationship->verification_status === 'pending') {
            throw new InvalidArgumentException('A relationship verification request is already pending for this learner.');
        }

        $existingPending = ParentChildInvitation::query()
            ->where('inviter_parent_user_id', $parent->id)
            ->where('child_user_id', $child->id)
            ->where('status', ParentChildInvitationStatus::Pending->value)
            ->latest('id')
            ->first();

        if ($existingPending !== null) {
            if ($existingPending->isExpired()) {
                $existingPending->update(['status' => ParentChildInvitationStatus::Expired->value]);
            } else {
                throw new InvalidArgumentException('An invitation is already pending for this learner.');
            }
        }

        $invitation = ParentChildInvitation::query()->create([
            'inviter_parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'invite_token' => (string) Str::uuid(),
            'status' => ParentChildInvitationStatus::Pending->value,
            'message' => $message ? trim($message) : null,
            'expires_at' => now()->addDays(14),
        ]);

        $invitation->load(['inviterParent:id,name', 'child:id,name']);
        $child->notify(new ParentChildInvitationReceivedNotification($invitation));

        return $invitation;
    }

    public function respondToInvitation(User $child, ParentChildInvitation $invitation, string $decision, ?string $note = null): ParentChildInvitation
    {
        if ((int) $invitation->child_user_id !== (int) $child->id) {
            throw new InvalidArgumentException('You are not allowed to respond to this invitation.');
        }

        if (($invitation->status instanceof ParentChildInvitationStatus ? $invitation->status->value : (string) $invitation->status) !== ParentChildInvitationStatus::Pending->value) {
            throw new InvalidArgumentException('This invitation is no longer pending.');
        }

        if ($invitation->isExpired()) {
            $invitation->update(['status' => ParentChildInvitationStatus::Expired->value]);
            throw new InvalidArgumentException('This invitation has already expired.');
        }

        $normalizedDecision = trim(strtolower($decision));
        if (! in_array($normalizedDecision, ['accept', 'reject'], true)) {
            throw new InvalidArgumentException('Invalid invitation decision.');
        }

        $decisionNote = $note ? trim($note) : null;

        $updatedInvitation = DB::transaction(function () use ($invitation, $normalizedDecision, $decisionNote) {
            if ($normalizedDecision === 'accept') {
                $link = ParentChildAccount::withTrashed()
                    ->where('parent_user_id', $invitation->inviter_parent_user_id)
                    ->where('child_user_id', $invitation->child_user_id)
                    ->first();

                $payload = [
                    'can_view_progress' => true,
                    'can_view_quiz_answers' => true,
                    'can_approve_content' => true,
                    'verification_status' => 'approved',
                    'verification_rejection_reason' => null,
                    'verification_reviewed_by' => null,
                    'verification_reviewed_at' => now(),
                    'verification_approved_at' => now(),
                    'relationship_verified_at' => now(),
                ];

                if ($link) {
                    if ($link->trashed()) {
                        $link->restore();
                    }

                    $link->update($payload);
                } else {
                    ParentChildAccount::query()->create([
                        'parent_user_id' => $invitation->inviter_parent_user_id,
                        'child_user_id' => $invitation->child_user_id,
                        ...$payload,
                    ]);
                }
            }

            $invitation->update([
                'status' => $normalizedDecision === 'accept'
                    ? ParentChildInvitationStatus::Accepted->value
                    : ParentChildInvitationStatus::Rejected->value,
                'decision_note' => $decisionNote,
                'responded_at' => now(),
            ]);

            return $invitation->fresh(['inviterParent:id,name', 'child:id,name']);
        });

        $updatedInvitation->inviterParent?->notify(new ParentChildInvitationRespondedNotification($updatedInvitation));

        return $updatedInvitation;
    }

    public function cancelInvitation(User $parent, ParentChildInvitation $invitation): ParentChildInvitation
    {
        if ((int) $invitation->inviter_parent_user_id !== (int) $parent->id) {
            throw new InvalidArgumentException('You are not allowed to cancel this invitation.');
        }

        if (($invitation->status instanceof ParentChildInvitationStatus ? $invitation->status->value : (string) $invitation->status) !== ParentChildInvitationStatus::Pending->value) {
            throw new InvalidArgumentException('Only pending invitations can be cancelled.');
        }

        $invitation->update([
            'status' => ParentChildInvitationStatus::Cancelled->value,
            'responded_at' => now(),
        ]);

        return $invitation->fresh();
    }

    public function getOutgoingInvitations(User $parent): Collection
    {
        $invitations = ParentChildInvitation::query()
            ->where('inviter_parent_user_id', $parent->id)
            ->with(['child:id,name,email,first_name,last_name', 'child.learnerProfile:id,user_id,username,birthdate'])
            ->latest('id')
            ->get();

        $this->expirePendingInvitations($invitations);

        return $invitations;
    }

    public function getIncomingInvitations(User $child): Collection
    {
        $invitations = ParentChildInvitation::query()
            ->where('child_user_id', $child->id)
            ->with(['inviterParent:id,name,email'])
            ->latest('id')
            ->get();

        $this->expirePendingInvitations($invitations);

        return $invitations;
    }

    private function resolveChildFromIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $normalized = strtolower($identifier);

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->orWhereHas('learnerProfile', function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(username) = ?', [$normalized]);
            })
            ->first();
    }

    private function resolveChildAge(User $child): ?int
    {
        if ($child->birthdate) {
            return Carbon::parse($child->birthdate)->age;
        }

        if ($child->learnerProfile?->birthdate) {
            return Carbon::parse($child->learnerProfile->birthdate)->age;
        }

        return null;
    }

    private function expirePendingInvitations(Collection $invitations): void
    {
        $invitations
            ->filter(fn (ParentChildInvitation $invitation) => $invitation->isPending() && $invitation->isExpired())
            ->each(function (ParentChildInvitation $invitation): void {
                $invitation->update(['status' => ParentChildInvitationStatus::Expired->value]);
                $invitation->status = ParentChildInvitationStatus::Expired;
            });
    }
}
