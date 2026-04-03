# Instructor Application Confirmation and Dual-Role Upgrade Design

**Date:** 2026-03-27  
**Status:** Approved  
**Approach:** Two-phase approval with learner confirmation before role grant, preserving simultaneous Learner and Instructor roles.

## Goal

Implement a clean, scalable instructor application workflow where:
- learners submit applications
- admins review and approve or reject
- approved learners receive database and email notifications
- learners explicitly accept or decline instructor access
- instructor role is granted only after learner acceptance
- learners retain learner access while gaining instructor access

## Problem Statement

The current implementation already supports learner application submission and admin review, but has one key mismatch with the desired flow:
- admin approval immediately grants instructor role and updates `users.role` to `instructor`

Required behavior is different:
- admin approval should only move the application into a waiting-for-learner-confirmation state
- role assignment should occur only after learner acceptance
- dual-role access (Learner + Instructor) must be supported without removing learner access

## Locked Decisions

1. **Two-phase lifecycle is mandatory**
- Phase 1: admin review outcome
- Phase 2: learner decision on approved application

2. **Dual-role is mandatory**
- accepted users keep Learner role
- accepted users also receive Instructor role
- access to both `/learn` and `/instructor` is expected

3. **Decline cooldown is required**
- when learner declines an approved offer, reapplication is blocked until cooldown expires
- cooldown duration is configurable (default recommendation: 14 days)

4. **Approval does not auto-upgrade role**
- admin approval sends notifications and opens decision window
- learner acceptance triggers actual role assignment

5. **Single workflow authority**
- `instructor_applications` remains the source of truth
- no additional invitation table for this phase

## Recommended Approach

Extend the existing `InstructorApplication` lifecycle into an explicit state machine and move role assignment logic to learner acceptance. Keep orchestration in `InstructorApplicationService` and keep controllers thin.

This approach is preferred because it:
- directly matches required behavior
- minimizes schema and code churn
- preserves existing submission/review flows
- provides clear, testable transitions and guards
- aligns with current service-layer architecture

## Alternatives Considered

### Option 1: State machine on existing instructor_applications (Chosen)
- Extend statuses and add confirmation/cooldown fields.
- Grant role only on learner acceptance.

**Pros**
- Lowest implementation risk
- Minimal new domain surface area
- Good auditability with current tables

**Cons**
- Needs careful state guards and migration mapping

### Option 2: Separate role-offer/invitation table
- Keep application review separate and create an offer record on approval.

**Pros**
- Very explicit domain boundaries
- Easier future extension for expirations/reminders

**Cons**
- Higher complexity for current scope
- More query and maintenance overhead

### Option 3: Keep auto-upgrade then support rollback on decline

**Pros**
- Fastest short-term patch

**Cons**
- Contradicts expected flow
- Risky rollback behavior and permission drift
- Harder to reason about consistency

## Architecture

Use `InstructorApplicationService` as the workflow orchestrator with explicit methods per transition.

### Ownership
- **Service layer:** transition guards, status updates, role assignment, notifications, auditing
- **Admin controller:** review actions only
- **Learner controller:** decision actions only
- **Routes:** admin review in `routes/admin.php`, learner confirmation actions in `routes/web.php`

### Authorization Principle
- role checks use Spatie roles as source of truth for new logic
- legacy `users.role` column can remain for compatibility/display, but not as the primary gate for dual-role decisions

## Lifecycle State Machine

### States
- `pending_review`
- `approved_waiting_confirmation`
- `rejected`
- `role_accepted`
- `role_declined`
- `withdrawn`

### Allowed Transitions
1. submit: none or eligible prior -> `pending_review`
2. admin approve: `pending_review` -> `approved_waiting_confirmation`
3. admin reject: `pending_review` -> `rejected`
4. learner accept: `approved_waiting_confirmation` -> `role_accepted`
5. learner decline: `approved_waiting_confirmation` -> `role_declined`
6. learner withdraw: `pending_review` -> `withdrawn`

Invalid transitions must return a guarded domain error and no-op.

## Data Model Direction

Add a new migration to evolve `instructor_applications`.

### Schema additions
- `reviewed_by` (nullable foreign key to users)
- `reviewed_at` (nullable timestamp)
- `learner_decision_at` (nullable timestamp)
- `learner_decision_ip` (nullable string)
- `decline_reason` (nullable text)
- `reapply_available_at` (nullable timestamp)

### Status domain update
Expand enum/status validation to include all lifecycle values listed above.

### Indexes
- index: (`user_id`, `status`)
- index: (`user_id`, `reapply_available_at`)
- optional index: `reviewed_at` for admin queue performance

### Backward compatibility mapping
For existing rows during migration:
- `pending` -> `pending_review`
- `rejected` -> `rejected`
- `approved` + user has instructor role -> `role_accepted`
- `approved` + user lacks instructor role -> `approved_waiting_confirmation`

## Duplicate and Reapply Controls

Submission is blocked when any of the following is true:
1. user already has Instructor role
2. user has an active application in `pending_review` or `approved_waiting_confirmation`
3. latest `role_declined` record has `reapply_available_at` in the future

All submit checks run in a transaction with row locking on latest application per user to avoid double-submit races.

## Service-Layer Responsibilities

### submitApplication(user, data)
- enforce duplicate/cooldown guards
- persist documents/metadata
- create `pending_review` application
- notify admins (database + mail)

### approveForConfirmation(application, admin)
- guard current state is `pending_review`
- update to `approved_waiting_confirmation`
- store reviewer identity/timestamp
- notify learner (database + mail) with accept/decline actions

### reject(application, admin, reason)
- guard current state is `pending_review`
- update to `rejected`
- store reviewer identity/timestamp and reason
- notify learner (database + mail)

### acceptInstructorRole(application, learner)
- guard owner and state `approved_waiting_confirmation`
- transaction:
  - ensure Learner role remains
  - assign Instructor role if missing
  - update/create instructor profile
  - write role transition audit record
  - update application to `role_accepted` with decision timestamp

### declineInstructorRole(application, learner, reason)
- guard owner and state `approved_waiting_confirmation`
- set `role_declined`
- set `reapply_available_at = now() + cooldown`
- store decision timestamp and optional reason

## Notification Design

Approval notification must explicitly state:
- application is approved by admin
- instructor access is not active yet
- learner must choose Accept or Decline

Channels:
- database notification
- email notification

Payload should include:
- `application_id`
- `status`
- `accept_url`
- `decline_url`

Mail failures must not fail workflow transaction; log warning and continue with database notification.

## Route and Controller Direction

### Admin routes (existing ownership)
- keep review endpoints under `routes/admin.php`
- use service methods that only review, not role grant

### Learner routes (new)
Under learner instructor prefix in `routes/web.php`:
- `GET /learn/instructor/decision/{application}` confirmation page
- `POST /learn/instructor/decision/{application}/accept`
- `POST /learn/instructor/decision/{application}/decline`

Controller methods in learner instructor application controller should only validate request ownership and call service methods.

## Access Control Expectations

After acceptance:
- user can access learner routes under `/learn` due to Learner role
- user can access instructor routes under `/instructor` due to Instructor role

Avoid using single-role helper methods as hard authorization gates for this flow. Prefer Spatie role checks.

## Edge Cases

1. Double admin review (two admins acting concurrently)
- second action rejected by state guard

2. Double learner acceptance submission
- idempotent handling: second call returns success/no-op

3. Decline before cooldown expires then reapply
- blocked with clear remaining cooldown feedback

4. Already-instructor user submits new application
- blocked immediately

5. User deactivated/archived before decision
- accept/decline denied by status/account checks

6. Notification transport errors
- workflow succeeds, warning logged

7. Legacy approved records
- migration mapping prevents orphan status semantics

## Testing Strategy

### Feature tests
1. admin approve moves to `approved_waiting_confirmation` and does not grant role
2. learner accept grants Instructor while preserving Learner role
3. learner decline sets cooldown and blocks reapply until expiry
4. only owner can accept/decline
5. only admin can approve/reject
6. learners with dual roles can access both dashboards

### Unit tests
1. service transition guard tests for every invalid transition
2. duplicate guard tests (pending + waiting confirmation + cooldown)
3. idempotency tests for repeated accept calls

### Notification tests
1. approval sends database + mail notifications
2. payload includes confirmation action links

## Rollout Notes

1. deploy migration first
2. deploy service/controller changes with state-guard compatibility
3. run full test suite
4. monitor logs for notification transport warnings and invalid transition attempts

## Out of Scope

- invitation expiry windows separate from cooldown
- multi-admin escalation workflow
- delegated reviewer roles/RBAC split for approvals
- UI redesign of admin/learner panels beyond required action surfaces
