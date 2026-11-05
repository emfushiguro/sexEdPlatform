# Registration verification refinements evidence

Implementation is in the existing `FacialVerification` checkout. Hosted Flow remains in use. No commit, push, deployment or real `.env` change was performed.

## Implemented behavior

- Applicant pending, change-request and approved screens use plain status messages. Application-owned admin/moderation environment indicators are removed; generated audit reasons are displayed in plain language while raw historical records remain unchanged.
- Protected Persona previews support center, left and right selfies alongside ID pages. They preserve admin authorization, current-owned evidence checks, allowlisted upstream hosts and no-store responses. Missing poses are handled without inventing evidence.
- School/institution ID capture occurs before Persona selfie verification. Camera preview, retake, explicit photo acceptance, rear-camera preference/device selection and permission errors use the existing capture lifecycle. The server prevents starting/replacing a school selfie inquiry without a matching current ID draft and prevents final submission before eligible selfie completion.
- Targeted learner corrections preserve acceptable evidence and require requested replacements. Retained Persona selfies remain bound to their original owned attempt; replacing an ID requires a renewed administrator portrait comparison. Final rejection blocks retry routes and synchronization reopening. Government ID plus selfie remain one correction unit.
- Targeted relationship corrections preserve acceptable documents in the next effective evidence round without duplicating stored files. Paired pages are replaced together; required document types, ownership and submission rounds remain enforced.
- Each relationship replacement is saved privately for its session/owner/case/round. Saved files survive reload, incomplete or modified saved sets cannot be submitted, and cleanup occurs after successful commit. Expired/abandoned drafts are pruned by the hourly scheduled command. Stale relationship approvals and Persona-backed guardian decisions are rejected before changing records.
- Verification notifications resolve current internal routes from recognized notification references instead of relying on a saved localhost/ngrok hostname. Guardian/dependent destinations differ appropriately. Signed links retain their signatures; unsafe external targets are rejected.
- The explicitly enabled staging demonstration guard remains separate from local mode. Persisted simulated approvals remain unavailable in ordinary production mode.

## Database and build

Inspected the development schema before applying two normal incremental migrations: `2026_10_28_000000_add_learner_correction_requests` and `2026_10_28_000100_add_relationship_correction_requests`. Existing counts remained 14 users, two learner identity cases and two guardian-dependent relationships after applying the migrations and browser tests. Browser data is isolated in `cc_db_test`; the development database was not reset or seeded.

The Vite production build succeeded. The public build was refreshed with `--emptyOutDir=false` to retain existing assets, and the previous manifest was backed up in ignored local browser artifacts. Composer dependencies were unchanged.

## Automated evidence

The final related regression suite passed **321 tests / 2,399 assertions**, with three PHPUnit deprecation notices. It covers identity, Persona policy/client, guardian onboarding, admin media/review, dependent relationships, saved correction drafts, invitation behavior and notification navigation. Separate draft-service unit checks passed **12 tests / 33 assertions**, with one deprecation notice. Camera lifecycle checks passed **12 JavaScript tests**.

After replacing the guardian pending screen's inbox shortcut with a direct status check, the presentation suite was rerun and passed **12 tests / 138 assertions**. Scoped Pint validation passed for **64 PHP files** and `git diff --check` passed. These are focused checks, not a full repository test run.

Connected review identified and resolved stale relationship approvals, missing current-attempt validation on guardian rejection, teen government-ID retry controls and abandoned draft cleanup. Follow-up fixes persist final guardian rejection correctly, retain session-bound draft ownership, avoid reacquiring a held draft lock during cleanup, validate replacement MIME contents and permit changing an unsuccessful government-ID attempt to a captured school ID.

## Playwright evidence

Used a separate local HTTP server configured as `staging` with the explicit demo flag, isolated `cc_db_test` and mocked outbound Persona responses. Synthetic evidence only; raw provider secrets and actual identity images were not used.

- Adult: real personal/account registration forms, email gate and locally signed verification link, full ID list, mocked Persona completion, pending access restriction, admin review and approval, then profile completion.
- Guardian: TIN selection, mocked Persona completion, pending review, admin approval with mandatory confirmations, guardian onboarding completed through its actual form.
- Evidence previews: ID front/back and all three synthetic selfie poses loaded through protected app endpoints. Environment indicators were absent from applicant and admin pages.
- Teen: school ID selection, simulated camera denial and recovery, camera capture/acceptance, saved ID before Hosted Flow, selfie completion, final confirmation and pending review. Admin requested only the ID; the teen captured its replacement and retained the accepted Persona selfie; renewed portrait confirmation and approval succeeded.
- Dependent creation: guardian entered personal details, location, credentials, synthetic birth record and two relationship documents through the existing creation wizard. The relationship entered review. Admin requested one document replacement; the notification opened the specific guardian correction form and the acceptable document was marked for retention.
- Dependent correction completion: saved the requested replacement, reloaded, confirmed the saved filename persisted with an empty native file input, submitted the corrections, then approved the new evidence round through the real admin form. Acceptable evidence was retained and the approved relationship no longer exposed a correction submission button.
- Mobile/direct requests: correction page settled without horizontal overflow at 390px; unrelated account received 403, stale/missing correction submission received 422, and an applicant could not retrieve admin-only provider media.

The initial dependent form field-count check ran before Alpine finished rendering; waiting for the actual replacement field resolved the test timing issue. The browser was restarted after an automatic tool-approval service usage limit interrupted the session. That interruption was a review-service failure, not an unsafe-action finding.

## Notification follow-up, 2026-10-29

A read-only inspection found existing admin verification alerts still resolving to the notification-list fallback when their stored host differed from the current app host. The prior recovery required recipient context, which the admin controller did not pass, and did not map admin review types. Some guardian/dependent status notifications had no action URL at all.

The admin controller now supplies its authenticated recipient. The resolver rebuilds known learner, guardian, dependent and relationship review destinations from validated record IDs. Guardian submission/rejection and dependent decisions resolve to their guarded status or dependent-management pages even when the old payload has no destination. Stored notification data remains unchanged; unknown external targets and cross-recipient access remain blocked.

The notification regression suites passed **37 tests / 161 assertions**, including stale-host recovery, invalid IDs, ownership and external-target rejection. Three changed PHP files passed Pint. Playwright signed into synthetic admin/guardian accounts and clicked seven notification-list links plus two header-dropdown links; every final page returned HTTP 200 at the expected local path, including the dependent correction page. Tests and browser fixtures used a dedicated `cc_db_notifications_test` database because the shared test schema showed concurrent migration interference; no development records were changed.

## Limits

Persona desktop layout, actual three-pose collection and provider-owned warnings remain Persona Dashboard responsibilities. Hosted provider camera/liveness/matching and real webhook delivery were not tested by these mocked browser journeys. Physical cameras, Hostinger deployment and the full repository test suite remain unverified. Synthetic PDF fixtures were minimal test files; PDF viewer rendering was not assessed.
