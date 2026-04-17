<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Models\User;
use App\Services\Moderation\Automation\ModerationAutomationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class AutomationExecutionLogTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_every_automation_execution_writes_a_log_record(): void
    {
        $service = app(ModerationAutomationService::class);
        $user = User::factory()->create();

        $service->upsertRules([
            [
                'key' => 'log-test-warning-rule',
                'name' => 'Log Test Warning Rule',
                'conditions' => ['min_violation_count' => 1],
                'action_type' => EnforcementActionType::Warning->value,
                'severity_level' => 'minor',
                'priority' => 100,
            ],
        ]);

        $service->evaluateForUser($user, [
            'violation_count' => 1,
            'violation_points' => 1,
            'highest_severity' => 'minor',
            'matched_violation_ids' => [101],
            'idempotency_key' => 'log-test-execution-1',
        ]);

        $service->evaluateForUser($user, [
            'violation_count' => 0,
            'violation_points' => 0,
            'highest_severity' => 'minor',
            'matched_violation_ids' => [],
            'idempotency_key' => 'log-test-execution-2',
        ]);

        $this->assertDatabaseCount('automation_rule_logs', 2);
        $this->assertDatabaseHas('automation_rule_logs', [
            'target_user_id' => $user->id,
            'status' => 'executed',
        ]);
        $this->assertDatabaseHas('automation_rule_logs', [
            'target_user_id' => $user->id,
            'status' => 'skipped',
        ]);
    }
}
