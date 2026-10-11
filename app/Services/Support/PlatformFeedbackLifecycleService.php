<?php

namespace App\Services\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\PlatformFeedbackHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformFeedbackLifecycleService
{
    private const ALLOWED = [
        'new' => ['reviewed', 'closed'],
        'reviewed' => ['resolved', 'closed'],
        'resolved' => ['reviewed', 'closed'],
    ];

    public function transition(PlatformFeedback $feedback, PlatformFeedbackStatus $target, User $reviewer): PlatformFeedback
    {
        $current = $feedback->status instanceof PlatformFeedbackStatus ? $feedback->status->value : (string) $feedback->status;
        if ($current !== $target->value && ! in_array($target->value, self::ALLOWED[$current] ?? [], true)) {
            abort(422, "Cannot transition feedback from {$current} to {$target->value}.");
        }

        if ($current !== $target->value && $target === PlatformFeedbackStatus::Resolved) {
            abort_unless(
                $feedback->messages()->where('sender_role', 'admin')->exists(),
                422,
                'Send a response before resolving this ticket.',
            );
        }

        return DB::transaction(function () use ($feedback, $target, $reviewer, $current): PlatformFeedback {
            $data = [];
            if ($current !== $target->value && $current === PlatformFeedbackStatus::New->value) {
                $data['reviewed_by'] = $reviewer->id;
                $data['reviewed_at'] = now();
            }
            if ($target === PlatformFeedbackStatus::Resolved && $current !== $target->value) {
                $data['resolved_at'] = now();
            }
            if ($target !== PlatformFeedbackStatus::Resolved && $current === PlatformFeedbackStatus::Resolved->value) {
                $data['resolved_at'] = null;
            }
            $data['status'] = $target;
            $feedback->update($data);
            if ($current !== $target->value) {
                PlatformFeedbackHistory::create([
                    'platform_feedback_id' => $feedback->id,
                    'actor_id' => $reviewer->id,
                    'from_status' => $current,
                    'to_status' => $target->value,
                ]);
            }

            return $feedback->fresh();
        });
    }
}
