<?php

namespace App\Jobs\Moderation;

use App\Models\User;
use App\Services\Moderation\Automation\ModerationAutomationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAutomationRulesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public int $targetUserId,
        public array $context = [],
    ) {
    }

    public function handle(ModerationAutomationService $automationService): void
    {
        $targetUser = User::query()->find($this->targetUserId);
        if (!$targetUser) {
            return;
        }

        $automationService->evaluateForUser($targetUser, $this->context);
    }
}
