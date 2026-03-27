# Admin Content Governance - Completed 2026-03-27

## Summary
Delivered an admin-governed module publication workflow. Instructor-authored modules now move through review states before learners can see them, while admins can also create and publish platform-owned modules directly.

## What Changed
1. Added governance persistence with `module_revisions`, `module_review_requests`, and governance columns on `modules`.
2. Added `ContentGovernanceService` to handle review submission, approval, rejection, revision snapshots, and direct admin publication.
3. Updated learner module reads to prefer approved published revision snapshots instead of live instructor draft state.
4. Added instructor submit and resubmit review actions with moderation status and feedback surfaced in instructor module views.
5. Added admin review queue, review detail, approval, rejection, dashboard metrics, and admin-owned module authoring flows.
6. Added audit and regression coverage for approval logging, rejection feedback, learner visibility, and moderated instructor lifecycle behavior.

## Lifecycle Notes
- Instructor-owned modules now start in a governed draft state.
- Admin approval publishes a specific revision and preserves instructor ownership attribution.
- Instructor edits to an already approved module stay hidden from learners until a newly submitted revision is approved.
- Admin-owned modules are created as `content_owner_type = admin`, published immediately, and backed by an approved revision snapshot.

## Entry Points
- Instructor review submission: `instructor.modules.review.submit`
- Instructor resubmission: `instructor.modules.review.resubmit`
- Admin review queue: `admin.content-reviews.index`
- Admin review detail: `admin.content-reviews.show`
- Admin approval and rejection: `admin.content-reviews.approve`, `admin.content-reviews.reject`
- Admin module authoring: `admin.modules.*`

## Verification Completed
- Focused governance schema, model, service, learner, instructor, admin UI, authoring, audit, and regression tests were executed.
- Database-heavy governance feature tests were run sequentially against the shared MySQL testing database to avoid cross-test interference.
- The implementation is ready for a broader full-suite validation pass in the active test environment.
