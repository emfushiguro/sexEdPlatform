<?php

namespace App\Services\Moderation\Automation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\AutomationRuleLog;
use App\Models\EnforcementAction;
use App\Models\ModerationAutomationRule;
use App\Models\ModerationAutomationRuleVersion;
use App\Models\User;
use App\Services\Moderation\EnforcementActionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModerationAutomationService
{
    public function __construct(
        private readonly ModerationAutomationValidator $validator,
        private readonly EnforcementActionService $enforcementActionService,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     * @return Collection<int, ModerationAutomationRule>
     */
    public function upsertRules(array $rules, ?User $admin = null): Collection
    {
        $this->validator->validateRuleSet($rules);

        return DB::transaction(function () use ($rules, $admin): Collection {
            $savedRules = [];

            foreach ($rules as $rulePayload) {
                $rule = ModerationAutomationRule::query()->firstOrNew([
                    'key' => (string) $rulePayload['key'],
                ]);

                $rule->fill([
                    'name' => (string) $rulePayload['name'],
                    'is_active' => (bool) ($rulePayload['is_active'] ?? true),
                    'priority' => (int) ($rulePayload['priority'] ?? 100),
                    'conditions' => is_array($rulePayload['conditions'] ?? null) ? $rulePayload['conditions'] : [],
                    'action_type' => (string) $rulePayload['action_type'],
                    'severity_level' => isset($rulePayload['severity_level']) ? (string) $rulePayload['severity_level'] : null,
                    'trigger_type' => (string) ($rulePayload['trigger_type'] ?? 'automatic'),
                    'metadata' => is_array($rulePayload['metadata'] ?? null) ? $rulePayload['metadata'] : null,
                ]);
                $rule->save();

                $nextVersion = (int) $rule->versions()->max('version_number') + 1;
                $version = ModerationAutomationRuleVersion::query()->create([
                    'rule_id' => $rule->id,
                    'version_number' => $nextVersion,
                    'conditions' => $rule->conditions,
                    'action_type' => $rule->action_type,
                    'severity_level' => $rule->severity_level,
                    'trigger_type' => $rule->trigger_type,
                    'created_by_admin_id' => $admin?->id,
                    'activated_at' => now(),
                    'is_active' => true,
                ]);

                $rule->versions()->where('id', '!=', $version->id)->update(['is_active' => false]);
                $rule->forceFill(['current_version_id' => $version->id])->save();

                $savedRules[] = $rule->fresh();
            }

            return new Collection($savedRules);
        });
    }

    /**
     * Stores a safe baseline preset set directly in DB records.
     *
     * @return Collection<int, ModerationAutomationRule>
     */
    public function seedDefaultPresets(?User $admin = null): Collection
    {
        return $this->upsertRules([
            [
                'key' => 'default-warning-threshold',
                'name' => 'Default Warning Threshold',
                'priority' => 300,
                'conditions' => [
                    'min_violation_count' => 1,
                    'min_violation_points' => 1,
                ],
                'action_type' => EnforcementActionType::Warning->value,
                'severity_level' => ViolationSeverity::Minor->value,
                'trigger_type' => 'automatic',
                'is_active' => true,
            ],
            [
                'key' => 'default-temporary-suspension-threshold',
                'name' => 'Default Temporary Suspension Threshold',
                'priority' => 200,
                'conditions' => [
                    'min_violation_count' => 2,
                    'min_violation_points' => 5,
                ],
                'action_type' => EnforcementActionType::TemporarySuspension->value,
                'severity_level' => ViolationSeverity::Major->value,
                'trigger_type' => 'automatic',
                'is_active' => true,
            ],
            [
                'key' => 'default-extended-suspension-threshold',
                'name' => 'Default Extended Suspension Threshold',
                'priority' => 100,
                'conditions' => [
                    'min_violation_count' => 3,
                    'min_violation_points' => 8,
                ],
                'action_type' => EnforcementActionType::ExtendedSuspension->value,
                'severity_level' => ViolationSeverity::Critical->value,
                'trigger_type' => 'automatic',
                'is_active' => true,
            ],
        ], $admin);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function evaluateForUser(User $user, array $context = []): AutomationRuleLog
    {
        $activeRules = ModerationAutomationRule::query()
            ->where('is_active', true)
            ->get();

        $matchedRules = $activeRules
            ->filter(fn (ModerationAutomationRule $rule): bool => $this->conditionsMatch($rule, $context))
            ->values();

        if ($matchedRules->isEmpty()) {
            return $this->writeLog([
                'rule_id' => null,
                'target_user_id' => $user->id,
                'matched_violation_ids' => array_values((array) ($context['matched_violation_ids'] ?? [])),
                'condition_snapshot' => $context,
                'action_executed' => null,
                'enforcement_action_id' => null,
                'status' => 'skipped',
                'idempotency_key' => $this->resolveIdempotencyKey($context),
                'executed_at' => now(),
            ]);
        }

        /** @var ModerationAutomationRule $selected */
        $selected = $matchedRules
            ->sort(function (ModerationAutomationRule $left, ModerationAutomationRule $right): int {
                $severityDelta = $this->severityRank((string) $right->severity_level)
                    <=> $this->severityRank((string) $left->severity_level);

                if ($severityDelta !== 0) {
                    return $severityDelta;
                }

                return (int) $left->priority <=> (int) $right->priority;
            })
            ->first();

        $enforcementAction = null;
        $status = 'executed';
        $errorMessage = null;

        try {
            $severity = $selected->severity_level
                ? ViolationSeverity::from((string) $selected->severity_level)
                : ViolationSeverity::Minor;
            $actionType = EnforcementActionType::from((string) $selected->action_type);

            $skipLadder = in_array($severity, [ViolationSeverity::Major, ViolationSeverity::Critical], true);
            $skipRationale = $skipLadder ? 'Automation rule escalation for high-severity policy match.' : null;

            $enforcementAction = $this->enforcementActionService->issueAction(
                user: $user,
                actionType: $actionType,
                severity: $severity,
                triggerType: (string) ($selected->trigger_type ?: 'automatic'),
                skipLadder: $skipLadder,
                skipRationale: $skipRationale,
            );
        } catch (\Throwable $exception) {
            $status = 'failed';
            $errorMessage = $exception->getMessage();
        }

        return $this->writeLog([
            'rule_id' => $selected->id,
            'target_user_id' => $user->id,
            'matched_violation_ids' => array_values((array) ($context['matched_violation_ids'] ?? [])),
            'condition_snapshot' => $context,
            'action_executed' => $selected->action_type,
            'enforcement_action_id' => $enforcementAction?->id,
            'status' => $status,
            'idempotency_key' => $this->resolveIdempotencyKey($context),
            'executed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeLog(array $payload): AutomationRuleLog
    {
        return AutomationRuleLog::query()->create($payload);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveIdempotencyKey(array $context): string
    {
        $provided = $context['idempotency_key'] ?? null;
        if (is_string($provided) && trim($provided) !== '') {
            return trim($provided);
        }

        return (string) Str::uuid();
    }

    /**
     * @param array<string, mixed> $context
     */
    private function conditionsMatch(ModerationAutomationRule $rule, array $context): bool
    {
        $conditions = is_array($rule->conditions) ? $rule->conditions : [];

        $count = (int) ($context['violation_count'] ?? 0);
        $points = (int) ($context['violation_points'] ?? 0);
        $highestSeverity = strtolower((string) ($context['highest_severity'] ?? ''));

        $minCount = isset($conditions['min_violation_count']) ? (int) $conditions['min_violation_count'] : null;
        if ($minCount !== null && $count < $minCount) {
            return false;
        }

        $minPoints = isset($conditions['min_violation_points']) ? (int) $conditions['min_violation_points'] : null;
        if ($minPoints !== null && $points < $minPoints) {
            return false;
        }

        $allowedHighest = $conditions['highest_severity_in'] ?? null;
        if (is_array($allowedHighest) && $allowedHighest !== []) {
            $normalized = array_map(static fn (mixed $value): string => strtolower((string) $value), $allowedHighest);
            if (!in_array($highestSeverity, $normalized, true)) {
                return false;
            }
        }

        return true;
    }

    private function severityRank(string $severity): int
    {
        return match (strtolower($severity)) {
            ViolationSeverity::Critical->value => 4,
            ViolationSeverity::Major->value => 3,
            ViolationSeverity::Moderate->value => 2,
            ViolationSeverity::Minor->value => 1,
            default => 0,
        };
    }
}
