<?php

namespace App\Services\Seminars;

use App\Enums\SeminarInteractionStatus;
use App\Enums\SeminarStatus;
use App\Models\Seminar;
use App\Models\SeminarComment;
use App\Models\SeminarQuestion;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SeminarInteractionService
{
    public function __construct(private readonly AgoraTokenService $tokens)
    {
    }

    public function postComment(User $user, Seminar $seminar, string $body): SeminarComment
    {
        $this->abortUnlessParticipantCanWrite($user, $seminar);

        return $seminar->comments()->create([
            'user_id' => $user->id,
            'body' => $body,
            'status' => SeminarInteractionStatus::Visible->value,
        ]);
    }

    public function postQuestion(User $user, Seminar $seminar, string $question): SeminarQuestion
    {
        $this->abortUnlessParticipantCanWrite($user, $seminar);

        return $seminar->questions()->create([
            'user_id' => $user->id,
            'question' => $question,
            'status' => SeminarInteractionStatus::Pending->value,
        ]);
    }

    public function hideComment(User $moderator, SeminarComment $comment, string $reason): SeminarComment
    {
        $comment->update([
            'status' => SeminarInteractionStatus::Hidden->value,
            'hidden_by' => $moderator->id,
            'hidden_at' => now(),
            'hidden_reason' => $reason,
        ]);

        return $comment->fresh();
    }

    public function hideQuestion(User $moderator, SeminarQuestion $question, string $reason): SeminarQuestion
    {
        $question->update([
            'status' => SeminarInteractionStatus::Hidden->value,
            'hidden_by' => $moderator->id,
            'hidden_at' => now(),
            'hidden_reason' => $reason,
        ]);

        return $question->fresh();
    }

    public function markQuestionAnswered(User $moderator, SeminarQuestion $question, string $answer): SeminarQuestion
    {
        $question->update([
            'status' => SeminarInteractionStatus::Answered->value,
            'answer' => $answer,
            'answered_by' => $moderator->id,
            'answered_at' => now(),
        ]);

        return $question->fresh();
    }

    private function abortUnlessParticipantCanWrite(User $user, Seminar $seminar): void
    {
        if ($seminar->status === SeminarStatus::Completed->value) {
            throw ValidationException::withMessages(['seminar' => 'This seminar is completed and interactions are read-only.']);
        }

        abort_unless(
            $this->tokens->isInJoinWindow($seminar)
                && ($this->tokens->canJoinAsAudience($user, $seminar) || $this->tokens->canPublish($user, $seminar)),
            403
        );
    }
}
