# Persona Single Submission Implementation Plan

> **For agentic workers:** Use executing-plans inline on the current branch. The user has approved the revised flow and current checkout.

**Goal:** Remove duplicate government-ID/selfie uploads while retaining manual approval and protected Persona previews.

**Architecture:** Add an explicit local/persona evidence source to existing applications. Authoritative synchronization submits eligible government cases through one idempotent service; existing manual-review services accept the correct evidence source. Existing local/school flows remain available.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, MySQL, PHPUnit.

## Global Constraints

- No worktree, reset, destructive migration, real .env modification, live identity mutation, or secret output.
- Tests target cc_db_test and run serially with --do-not-cache-result.
- PH and exact supported ID class remain mandatory. No automatic application approval or local Persona image storage.
- Preserve unrelated .gitignore and generated build changes already in the checkout.

## Tasks

- [x] Add regression tests for authoritative submission, duplicate deliveries, review without local government images, school review without local selfie, and preview authorization.
  - Create `tests/Feature/Identity/PersonaSingleSubmissionTest.php` using real local models, fake Persona API responses and notifications.
  - Run `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaSingleSubmissionTest.php`; observe failures before implementing.
- [x] Add evidence-source migration and `PersonaReviewSubmission` service; call it during synchronization. Update round lookup and admin approval gates without allowing stale/rejected rounds to resubmit.
  - Modify `PersonaInquirySynchronizer`, `PersonaInquiryService`, `LearnerIdentityReview`, and the learner/guardian controllers/views. Source columns default to `local`; new provider submissions use `persona`.
  - Reuse existing submitted notifications and audits after commit; repeated synchronization must not duplicate them.
- [x] Fix recovery and previews.
  - Update policy for completed/approved versus declined/needs-review. Update shared user partial for mismatches, processing, and corrected-ID replacement.
  - Render protected inline previews in the existing admin Persona summary with availability errors and source labels. Harden endpoint association checks.
- [x] Verify and document.
  - Run the new tests and existing Persona, learner, guardian and admin regressions serially. Inspect route list, PHP syntax, whitespace and focused formatting.
  - Inspect additive SQL, apply only the new migration, and record automated versus unrun live/browser evidence in the verification record and runbook.
