<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Models\User;
use App\Services\Moderation\Automation\ModerationAutomationService;
use App\Services\Moderation\Automation\ModerationAutomationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class AutomationRulesSafetyTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_invalid_thresholds_are_rejected(): void
    {
        $validator = app(ModerationAutomationValidator::class);

        $this->expectException(\InvalidArgumentException::class);

        $validator->validateRulePayload([
            'key' => 'bad-threshold',
            'name' => 'Bad Threshold Rule',
            'conditions' => [
                'min_violation_points' => -2,
            ],
            'action_type' => EnforcementActionType::Warning->value,
            'severity_level' => 'minor',
        ]);
    }

    public function test_invalid_resulting_actions_are_rejected(): void
    {
        $validator = app(ModerationAutomationValidator::class);

        $this->expectException(\InvalidArgumentException::class);

        $validator->validateRulePayload([
            'key' => 'bad-action',
            'name' => 'Bad Action Rule',
            'conditions' => [
                'min_violation_count' => 2,
            ],
            'action_type' => 'unknown_action',
            'severity_level' => 'moderate',
        ]);
    }

    public function test_conflicting_rule_conditions_are_rejected(): void
    {
        $validator = app(ModerationAutomationValidator::class);

        $this->expectException(\InvalidArgumentException::class);

        $validator->validateRuleSet([
            [
                'key' => 'conflict-1',
                'name' => 'Conflict Rule 1',
                'conditions' => ['min_violation_points' => 8],
                'action_type' => EnforcementActionType::TemporarySuspension->value,
                'severity_level' => 'major',
            ],
            [
                'key' => 'conflict-2',
                'name' => 'Conflict Rule 2',
                'conditions' => ['min_violation_points' => 8],
                'action_type' => EnforcementActionType::Warning->value,
                'severity_level' => 'minor',
            ],
        ]);
    }

    public function test_highest_severity_action_is_chosen_when_multiple_rules_match(): void
    {
        $service = app(ModerationAutomationService::class);
        $user = User::factory()->create();

        $service->upsertRules([
            [
                'key' => 'rule-major',
                'name' => 'Major Rule',
                'conditions' => [
                    'min_violation_points' => 10,
                    'min_violation_count' => 2,
                ],
                'action_type' => EnforcementActionType::TemporarySuspension->value,
                'severity_level' => 'major',
                'priority' => 10,
            ],
            [
                'key' => 'rule-critical',
                'name' => 'Critical Rule',
                'conditions' => [
                    'min_violation_points' => 8,
                    'min_violation_count' => 1,
                ],
                'action_type' => EnforcementActionType::ExtendedSuspension->value,
                'severity_level' => 'critical',
                'priority' => 20,
            ],
        ]);

        $service->evaluateForUser($user, [
            'violation_points' => 10,
            'violation_count' => 2,
            'highest_severity' => 'critical',
            'matched_violation_ids' => [11, 12],
        ]);

        $this->assertDatabaseHas('enforcement_actions', [
            'user_id' => $user->id,
            'action_type' => EnforcementActionType::ExtendedSuspension->value,
        ]);
    }

    public function test_default_presets_are_seeded_as_database_records(): void
    {
        $service = app(ModerationAutomationService::class);

        $service->seedDefaultPresets();

        $this->assertDatabaseCount('moderation_automation_rules', 3);
        $this->assertDatabaseHas('moderation_automation_rules', ['key' => 'default-warning-threshold']);
        $this->assertDatabaseHas('moderation_automation_rules', ['key' => 'default-temporary-suspension-threshold']);
        $this->assertDatabaseHas('moderation_automation_rules', ['key' => 'default-extended-suspension-threshold']);
    }
}
