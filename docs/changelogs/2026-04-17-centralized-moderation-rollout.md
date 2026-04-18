# 2026-04-17 Centralized Moderation Rollout

## Summary

- Centralized moderation pipeline enabled with dual-write adapters for legacy moderation sources.
- Suspension and appeal lifecycle now records centralized moderation cases while preserving legacy workflows.
- Backfill tooling added to migrate historical moderation artifacts into centralized cases.
- Parity reconciliation report added to validate source-to-centralized record consistency before cutover.

## Backfill Command

- Command: `php artisan moderation:backfill-centralized`
- Reconciliation only: `php artisan moderation:backfill-centralized --reconcile-only`

### Backfill Sources

- `module_review_requests` -> `moderation_cases` (`module_review`)
- `message_reports` -> `moderation_cases` (`chat_report`)
- `content_reports` -> `moderation_cases` (`learner_report`)
- `instructor_applications` -> `moderation_cases` (`instructor_application`)

### Output

- Processed counts per source and total processed records
- Parity summary table with:
  - legacy count
  - centralized count
  - delta
- Mismatch warning when any source delta is non-zero

## Validation Notes

- Backfill operation is idempotent through source/content scoped upsert behavior.
- Reconciliation report should be clean (`delta = 0`) for all sources before cutover.
- Keep dual-write active until parity is stable across repeated runs.
