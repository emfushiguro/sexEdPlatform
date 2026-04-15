# Dynamic Gamification Configuration Design

## 1. Purpose and Goals
Transition the current gamification system from hardcoded mechanics to a unified dynamic policy that is managed by admins and consumed consistently by learner and admin flows.

Primary goals:
- Remove hardcoded values from gamification logic and learner-facing rules copy.
- Provide a centralized admin interface for gamification settings.
- Ensure backend behavior and frontend display always resolve from the same configuration source.
- Add strict validation and safety checks to prevent invalid configurations.
- Preserve current learner progress while applying new rules forward.
- Support future expansion (badges, event rewards, multipliers) without major refactoring.

## 2. Approved Product Decisions
The following decisions were approved in brainstorming:
- Storage model: single active policy record with grouped JSON sections.
- Apply behavior: save and apply immediately globally.
- Level progression: hybrid (explicit thresholds override + formula fallback).
- Level-up bonus: flat configurable bonus.
- Quiz rewards: score-band rewards.
- Reward sources now: topic, lesson, module, quiz, certificate.
- Streak qualifying action: topic completion.
- Streak milestones: configurable milestone list.
- Saver behavior: auto-consume on missed-day protection.
- Premium shields: remain subscription entitlement controlled, not gamification policy controlled.
- Validation: strict field and cross-field checks.
- Existing learner progress: preserved; no forced recalculation.
- Learner rules page: static explanatory copy with dynamic numbers.
- Admin settings UX: tabbed sections.
- Governance: version history with restore.
- Testing depth: service + feature + selective UI assertions.

## 3. Scope
In scope:
- Dynamic gamification configuration persistence and resolution.
- Admin gamification settings management page.
- Learner gamification logic refactor to dynamic configuration.
- Learner rules display refactor to dynamic values.
- Validation, safeguards, audit trail, and restore flow.
- Regression tests around rewards, leveling, streaks, shields, and UI value rendering.

Out of scope:
- Badge and achievement rule designer.
- Event campaign builder and seasonal multipliers.
- Global historical score/level recomputation job.
- Subscription entitlement redesign.

## 4. Current State Findings
Current implementation uses hardcoded values in several locations:
- Service-level mechanics in `GamificationService` (level thresholds, streak milestones).
- Controller-level rewards/costs in learner controllers (lesson/topic/module/quiz/certificate rewards, shield refill costs, streak saver costs).
- Model-level shield cap/default logic in `UserDailyShield`.
- Learner dashboard and rules UI copy with hardcoded numbers and caps.

Impact:
- Admin cannot reliably control mechanics.
- Backend and UI can drift.
- Future expansions require repeated code edits.

## 5. Target Architecture
### 5.1 Core Pattern
Adopt a single-source dynamic policy architecture:
1. Policy persistence layer stores active policy plus historical versions.
2. Resolver layer loads active policy, merges defaults, validates shape/invariants, and returns typed runtime config.
3. Domain services use only resolved config.
4. Controllers orchestrate and views display values computed from resolved config.

### 5.2 Source of Truth
All gamification calculations and learner rule displays must resolve from one runtime object produced by a policy resolver service.

No controller, service, or view should rely on hardcoded values for:
- points earned
- streak milestone rewards
- saver costs/caps
- shield refill costs/caps
- level progression thresholds or formula constants

## 6. Configuration Model
## 6.1 Persistence Strategy
Use a single active policy record with grouped JSON sections.

Proposed tables:
1. `gamification_policies`
- `id`
- `is_active` (boolean)
- `policy_payload` (json)
- `version_label` (nullable string)
- `change_summary` (nullable text)
- `updated_by` (foreign key users)
- timestamps

2. `gamification_policy_versions`
- `id`
- `policy_id` (foreign key)
- `policy_payload` (json snapshot)
- `change_summary` (nullable text)
- `changed_by` (foreign key users)
- `created_at`

Restore strategy:
- Restoring any version creates a new active policy snapshot, preserving immutable history.

### 6.2 Logical Groups
`policy_payload` top-level structure:
- `points_config`
- `streak_config`
- `leveling_config`
- `shield_config`
- `safeguards_config`

### 6.3 Draft Payload Shape
```json
{
  "points_config": {
    "topic_complete_points": 10,
    "lesson_complete_points": 15,
    "module_complete_points": 100,
    "certificate_earned_points": 50,
    "quiz_bands": {
      "perfect_score_points": 30,
      "pass_score_points": 25,
      "fail_attempt_points": 5
    },
    "level_up_bonus_points": 20
  },
  "streak_config": {
    "qualifying_event": "topic_completion",
    "auto_consume_saver": true,
    "max_savers_held": 3,
    "milestones": [
      { "days": 7, "bonus_points": 50, "priority": 20 },
      { "days": 30, "bonus_points": 200, "priority": 10 }
    ]
  },
  "leveling_config": {
    "formula": {
      "base_xp_per_level": 100,
      "growth_mode": "linear",
      "growth_factor": 1
    },
    "explicit_thresholds": {
      "1": 0,
      "2": 100,
      "3": 200
    },
    "threshold_resolution": "explicit_then_formula"
  },
  "shield_config": {
    "daily_shields_default": 3,
    "max_shields_per_day_cap": 3,
    "refill_single_cost_points": 50,
    "refill_full_cost_points": 100,
    "refill_full_target_shields": 3
  },
  "safeguards_config": {
    "allow_negative_rewards": false,
    "allow_negative_costs": false,
    "enforce_monotonic_thresholds": true,
    "enforce_unique_milestone_days": true
  }
}
```

## 7. Admin Settings Management Design
### 7.1 Routes and Ownership
Admin-only routes in `routes/admin.php` under a dedicated prefix:
- `GET /admin/gamification-settings` (index)
- `PUT /admin/gamification-settings` (update active policy)
- `GET /admin/gamification-settings/history` (version history)
- `POST /admin/gamification-settings/restore/{version}` (restore)

### 7.2 Controller and Requests
- Controller remains thin and delegates to services.
- Form Request handles field-level validation and basic shape checks.
- Domain validator handles cross-field logical checks.

### 7.3 UI Structure
New admin page with tabs:
1. Points
2. Streak
3. Leveling
4. Shields
5. Safeguards and Review
6. History

UX rules:
- Inputs grouped with clear labels and helper text.
- Numeric bounds shown inline.
- Summary panel displays effective rewards and costs.
- Save applies immediately and shows clear success/failure messaging.

## 8. Runtime Resolution and Caching
### 8.1 Resolver Service
Add a dedicated resolver service that:
1. Fetches active policy.
2. Merges with baseline defaults.
3. Normalizes types.
4. Validates invariants.
5. Returns typed config object/array.

### 8.2 Cache Strategy
- Cache resolved policy for short duration.
- Invalidate cache on save and restore.
- Fallback behavior: if active policy is unreadable, fallback to last known valid policy; if unavailable, fallback to immutable defaults and log error.

## 9. Domain Logic Refactor
### 9.1 Centralize Mechanics in Services
Refactor gamification logic to consume resolver values:
- `GamificationService`
  - award points for actions from config keys
  - level calculation via leveling resolver
  - streak milestone bonus lookup from milestone list
- Shield and saver operations consume config caps/costs.

### 9.2 Learner Controllers to Refactor
Update hardcoded rewards and costs in:
- `LessonController`
- `QuizController`
- `StreakSaverController`
- `ShieldRefillController`
- certificate award paths

### 9.3 Model-Level Cap/Default Logic
`UserDailyShield` must no longer hardcode cap/default values directly; values resolve from policy config.

## 10. Learner UI Alignment
### 10.1 Dashboard and Components
Update learner components to use dynamic values from resolved config:
- XP display and progress denominator behavior based on leveling config.
- Saver buy button eligibility and tooltip using dynamic cost/cap.
- Shield visuals and textual caps using dynamic values.

### 10.2 Rules Page
Maintain clear static explanation structure while injecting all numeric values dynamically:
- lesson/topic/module/certificate points
- quiz band rewards
- streak milestones and bonus amounts
- saver cost and max hold
- shield daily count and refill costs

Result: no stale or mismatched learner explanations.

## 11. Validation and Safety Controls
### 11.1 Field Rules
Examples:
- all point values are integers and >= 0
- all cost values are integers and >= 0
- `daily_shields_default` and cap are positive integers
- `max_savers_held` is non-negative integer

### 11.2 Cross-Field Invariants
- full refill cost >= single refill cost
- `refill_full_target_shields` <= `max_shields_per_day_cap`
- milestone days are unique and > 0
- milestone bonus points >= 0
- explicit thresholds are monotonic increasing by level

### 11.3 Stability Policies
- Reject invalid payload atomically.
- Do not mutate active policy on failed validation.
- Keep restore operation validated before activation.

## 12. Backward Compatibility and Rollout
### 12.1 No Progress Reset
Do not alter existing learner `score`, `level`, `total_points`, `streak_count`, or saver inventory during rollout.

### 12.2 Forward Application
New settings apply only to events after activation.

### 12.3 Seed Baseline Policy
Create initial policy snapshot mirroring current production behavior to avoid user-visible jumps at deployment.

## 13. Testing Strategy
### 13.1 Unit Tests
- resolver merge and fallback behavior
- leveling hybrid resolution
- milestone selection and reward logic
- invariant validation failures

### 13.2 Feature Tests
- admin update valid payload activates policy
- invalid payload rejected with no active change
- version restore activates expected snapshot
- learner reward flows use configured values
- saver and shield operations obey configured caps/costs

### 13.3 Selective UI Tests
- learner rules page renders current policy values
- key dashboard counters match resolved values

## 14. Security and Authorization
- Restrict policy management to authorized admin permission.
- Log actor and request metadata for policy updates/restores.
- Keep payload audit snapshots immutable.

## 15. Future Extensibility
Planned extension points without schema redesign:
- `achievements_config`
- `event_rewards_config`
- `bonus_multipliers_config`

Because grouped payload + resolver abstraction is adopted now, new mechanics can be added as new config sections with additive validation rules.

## 16. Acceptance Criteria
Design is successful when:
- All gamification calculations resolve from active dynamic policy.
- Admin can update settings from one page and changes apply globally.
- Learner behavior and rules display remain consistent with policy values.
- Invalid configurations are blocked safely.
- Version history and restore work without losing audit trace.
- Existing learner progress remains intact.

---

Approval status: Approved by user after full design review on 2026-04-16.
