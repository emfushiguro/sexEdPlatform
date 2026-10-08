<?php

namespace App\Services\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\PlatformFeedbackMessage;
use App\Models\User;
use App\Notifications\PlatformFeedbackMessageNotification;
use App\Services\Chat\SupportAdminResolver;
use Illuminate\Support\Facades\DB;

class PlatformFeedbackConversationService
{
    public function __construct(
        private readonly PlatformFeedbackLifecycleService $lifecycle,
        private readonly SupportAdminResolver $adminResolver,
    ) {}

    public function send(PlatformFeedback $ticket, User $sender, string $body): PlatformFeedbackMessage
    {
        $isAdmin = $sender->hasRole('admin') || $sender->role === 'admin';
        $isOwner = (int) $ticket->user_id === (int) $sender->id;

        abort_unless($isAdmin || $isOwner, 403);

        $message = DB::transaction(function () use ($ticket, $sender, $body, $isAdmin, $isOwner): PlatformFeedbackMessage {
            $locked = PlatformFeedback::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($isAdmin) {
                abort_unless((bool) $locked->may_contact, 422, 'The ticket author did not request follow-up messages.');
            }

            abort_if(in_array($locked->status, [PlatformFeedbackStatus::Closed, PlatformFeedbackStatus::Withdrawn], true), 422, 'This ticket is closed to new messages.');

            if ($isAdmin && $locked->status === PlatformFeedbackStatus::New) {
                $locked = $this->lifecycle->transition($locked, PlatformFeedbackStatus::Reviewed, $sender);
            }

            if ($isOwner) {
                abort_unless($locked->messages()->where('sender_role', 'admin')->exists(), 422, 'Wait for the platform team to respond before sending a reply.');

                if ($locked->status === PlatformFeedbackStatus::Resolved) {
                    $locked = $this->lifecycle->transition($locked, PlatformFeedbackStatus::Reviewed, $sender);
                }
            }

            return $locked->messages()->create([
                'sender_id' => $sender->id,
                'sender_role' => $this->senderRole($sender),
                'body' => $body,
            ]);
        });

        $recipient = $isAdmin
            ? $ticket->user
            : $this->reviewRecipient($ticket->fresh(), $sender);
        $recipient?->notify(new PlatformFeedbackMessageNotification($message));

        return $message;
    }

    private function reviewRecipient(PlatformFeedback $ticket, User $sender): ?User
    {
        $reviewer = $ticket->reviewer;
        if ($reviewer && ($reviewer->status === null || $reviewer->status === User::STATUS_ACTIVE) && ($reviewer->hasRole('admin') || $reviewer->role === 'admin')) {
            return $reviewer;
        }

        return $this->adminResolver->resolve($sender->id);
    }

    private function senderRole(User $sender): string
    {
        if ($sender->hasRole('admin') || $sender->role === 'admin') {
            return 'admin';
        }

        if ($sender->role === 'instructor') {
            return 'instructor';
        }

        if ($sender->role === 'connector') {
            return 'connector';
        }

        if ($sender->role === 'parent' || $sender->account_type === User::ACCOUNT_TYPE_PARENT) {
            return 'parent';
        }

        return 'learner';
    }
}
