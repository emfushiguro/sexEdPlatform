<?php

namespace App\Services\Moderation\Automation;

use App\Enums\EnforcementActionType;

class ModerationAutomationValidator
{
    /**
     * @param array<string, mixed> $rule
     */
    public function validateRulePayload(array $rule): void
    {
        $conditions = is_array($rule['conditions'] ?? null) ? $rule['conditions'] : [];

        $minCount = $conditions['min_violation_count'] ?? null;
        if ($minCount !== null && (!is_numeric($minCount) || (int) $minCount < 0)) {
            throw new \InvalidArgumentException('min_violation_count must be zero or greater.');
        }

        $minPoints = $conditions['min_violation_points'] ?? null;
        if ($minPoints !== null && (!is_numeric($minPoints) || (int) $minPoints < 0)) {
            throw new \InvalidArgumentException('min_violation_points must be zero or greater.');
        }

        $actionType = (string) ($rule['action_type'] ?? '');
        if (!in_array($actionType, EnforcementActionType::values(), true)) {
            throw new \InvalidArgumentException('Unsupported automation action type.');
        }

        $severityLevel = $rule['severity_level'] ?? null;
        if ($severityLevel !== null && !in_array((string) $severityLevel, ['minor', 'moderate', 'major', 'critical'], true)) {
            throw new \InvalidArgumentException('Unsupported severity level.');
        }

        $triggerType = (string) ($rule['trigger_type'] ?? 'automatic');
        if (!in_array($triggerType, ['automatic', 'manual'], true)) {
            throw new \InvalidArgumentException('Unsupported trigger type.');
        }

        if ($triggerType !== 'manual' && $actionType === EnforcementActionType::PermanentSuspension->value) {
            throw new \InvalidArgumentException('Permanent suspension can only be configured for manual trigger type.');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rules
     */
    public function validateRuleSet(array $rules): void
    {
        $seenConditions = [];

        foreach ($rules as $rule) {
            $this->validateRulePayload($rule);

            $conditions = is_array($rule['conditions'] ?? null) ? $rule['conditions'] : [];
            ksort($conditions);
            $fingerprint = json_encode($conditions);
            if ($fingerprint === false) {
                throw new \InvalidArgumentException('Invalid rule conditions payload.');
            }

            $actionSignature = sprintf(
                '%s|%s|%s',
                (string) ($rule['action_type'] ?? ''),
                (string) ($rule['severity_level'] ?? ''),
                (string) ($rule['trigger_type'] ?? 'automatic')
            );

            if (!isset($seenConditions[$fingerprint])) {
                $seenConditions[$fingerprint] = $actionSignature;
                continue;
            }

            if ($seenConditions[$fingerprint] !== $actionSignature) {
                throw new \InvalidArgumentException('Conflicting automation rules share the same conditions.');
            }
        }
    }
}
