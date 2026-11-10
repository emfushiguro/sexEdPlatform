# Onboarding Navigation Guides - End-to-End QA Runbook

Status: implementation runbook for the dynamic role-based onboarding navigation guides.

This document is the manual verification companion to:

- `docs/superpowers/specs/2026-11-09-onboarding-navigation-guides-design.md`
- `docs/superpowers/plans/2026-11-09-onboarding-navigation-guides.md`

Automated tests, source inspection, and a successful build are useful evidence, but they do not replace the browser and deployment checks in this runbook.

## 1. Implemented scope

| Area | Implemented behavior | Primary surfaces |
| --- | --- | --- |
| Domain and persistence | Stable guide aggregate, immutable versions, one user/version state row, unique state constraint, role/status constants, factories, and User relations | `onboarding_guides`, `onboarding_guide_versions`, `user_onboarding_guide_states` |
| Permission | `manage onboarding guides` is part of the canonical permission catalog and is granted incrementally to Admin | Admin middleware, FormRequests, navigation visibility |
| Lifecycle | Draft creation, safe draft editing, private video replacement, initial publication, revision publication, supersession, unpublish, republish, and archive | `OnboardingGuideLifecycleService`, Admin guide pages |
| Audience | Current published guide resolution, learner/instructor role context, dashboard eligibility, suspension/identity/guardian/child gates, effective state, and aggregate analytics | `OnboardingGuideAudienceService` |
| Private video | Authenticated inline playback, admin preview, MIME validation, private no-store headers, `nosniff`, and byte-range support from the file response | `/onboarding-guides/versions/{version}/video` |
| State endpoints | Authenticated idempotent dismiss and complete actions tied to the current exact version and acting user | `/dismiss`, `/complete` endpoints |
| User experience | Learner and Instructor Guide pages, permanent sidebar links, dashboard modal, explicit completion, dismissal, retryable errors, and responsive native dialog presentation | Learner/Instructor dashboards and Guide pages |
| Regression coverage | PHPUnit feature coverage for domain, lifecycle, audience, media, admin, state, pages, and modal behavior; Node coverage for modal/upload/video helpers | `tests/Feature/Onboarding`, `tests/Feature/Admin`, `tests/Unit/JavaScript` |
| Migration state | Both additive onboarding migrations are applied to the current development database | `2026_11_09_000001`, `2026_11_09_000002` |

The final implementation also fixes the unpublish -> republish path: republishing the same current version does not require a revision-only `requires_recompletion` choice and does not create a new version.

## 2. Safety and evidence rules

- Use synthetic, non-sensitive video files and test accounts only.
- Never run `migrate:fresh`, `db:wipe`, `TRUNCATE`, `DROP TABLE`, an unscoped `DELETE`, or a destructive seeder.
- Automated PHPUnit tests must use the isolated `cc_db_test` database from `phpunit.xml`; do not treat the development database as a disposable test database.
- Manual browser testing should use local QA or staging data. If local development data is used, create clearly named QA guides and archive them after testing; do not delete their history or files.
- Use a real, playable small MP4/WebM for playback checks. A minimal synthetic file is sufficient for upload and range-header checks but may not decode in a browser.
- Do not record raw private storage paths, document numbers, private URLs, or real identity evidence in screenshots or test notes.
- Record guide ID, version number, target role, user persona, state transition, and result. Do not record private file paths.
- Source tests and local browser results do not prove Hostinger, staging, provider, or production behavior. Those are separate release gates.

## 3. Test personas and fixtures

Use isolated accounts with known credentials. The repository's test seeders may provide equivalents, but confirm the current account data before testing.

| Persona | Required condition | Purpose |
| --- | --- | --- |
| Admin manager | Admin role or `manage onboarding guides` permission | Create, edit, preview, publish, unpublish, supersede, archive, and inspect history |
| Eligible Learner | Active learner with a complete learner dashboard profile and no blocking identity/guardian/child gate | Learner modal, Guide page, dismissal, and completion |
| Eligible Instructor | Active instructor who can access the Instructor dashboard | Instructor role context and modal |
| Ineligible learner | Inactive, suspended, incomplete-profile, pending/rejected-identity, or blocked child/guardian account | Audience exclusion and empty state |
| Ordinary authenticated user | Authenticated user without guide-management permission and without the target role context | Admin denial and cross-role denial |
| Guest | Unauthenticated browser | Authentication and private media denial |
| Second eligible user | Same target role as the first eligible user | Analytics and per-user state isolation |

Recommended local seed accounts, where present, are the Admin, an approved Instructor, and learner accounts such as `kid@test.local`, `teen@test.local`, or `premium.learner@test.local`. Never assume a seed account is eligible without checking its current role, status, profile, and verification records.

## 4. Environment preparation

### 4.1 Confirm checkout and data safety

1. Run `git status --short`.
2. Identify unrelated generated build or view changes before testing.
3. Do not stage or overwrite unrelated changes.
4. Confirm the branch and local application URL.

Expected:

- The test is run from the intended branch.
- Existing user-owned changes remain present after testing.
- No database reset command is used.

### 4.2 Apply normal migrations

Run:

```powershell
php artisan migrate:status
php artisan migrate --force
```

Expected:

- `2026_11_09_000001_create_onboarding_guide_tables` is `Ran`.
- `2026_11_09_000002_add_manage_onboarding_guides_permission` is `Ran`.
- A fully current database reports `Nothing to migrate.`
- Existing users, roles, profiles, videos, and progress remain intact.

If a migration is pending, apply it normally and rerun `migrate:status`. Do not use a fresh, wipe, truncate, drop, or destructive reseed command to repair migration state.

### 4.3 Build and run the application

Before browser testing, build the frontend assets that the local application serves:

```powershell
pnpm.cmd build
php artisan serve --host=127.0.0.1 --port=8000
```

If the repository uses a separate web server, start that server instead. Confirm the generated manifest points to the current source bundle before concluding that browser JavaScript is broken.

Prepare:

- one playable MP4 or WebM smaller than 100 MiB;
- one invalid extension or MIME sample;
- one file exactly at the accepted 100 MiB boundary if the environment can create it safely;
- browser DevTools or an HTTP client for status/header/range checks;
- a clean browser profile or cleared session for first-presentation tests.

## 5. Automated baseline before manual QA

Run the focused backend regression suite from the repository root:

```powershell
php vendor/bin/phpunit --do-not-cache-result `
  tests/Feature/Onboarding `
  tests/Feature/Admin/AdminOnboardingGuideManagementTest.php `
  tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php `
  tests/Feature/Rbac/RbacBladePermissionVisibilityTest.php `
  tests/Feature/Instructor/VideoUploadTest.php
```

Run the JavaScript checks:

```powershell
node --test `
  tests/Unit/JavaScript/video-upload-form.test.js `
  tests/Unit/JavaScript/onboarding-guide-modal.test.js `
  tests/Unit/JavaScript/video-player.test.js
```

Run the supporting checks:

```powershell
php artisan view:cache
vendor\bin\pint --test app\Http\Requests\Admin\PublishOnboardingGuideRequest.php app\Http\Controllers\Onboarding\GuideController.php
php artisan route:list --name=onboarding-guides
php artisan route:list --name=learner.guide
php artisan route:list --name=instructor.guide
```

Expected:

- Focused PHPUnit is green.
- All listed Node tests pass.
- Blade templates cache successfully.
- The changed request/controller files pass targeted Pint.
- Admin, private-video, state, Learner Guide, and Instructor Guide routes are present.

Do not call this manual QA run a release sign-off if the focused baseline is red.

## 6. Manual end-to-end test cases

### OBG-ADM-01 - Admin permission and navigation

1. Open the application as a guest and request the Admin Onboarding Guides index.
2. Sign in as an ordinary Learner or Instructor without guide-management permission.
3. Request each Admin guide action directly: index, create, edit, history, update, publish, unpublish, and archive.
4. Sign in as an Admin manager.
5. Confirm the Admin sidebar contains **Onboarding Guides** under Learning Contents.
6. Open the index page and confirm the total, published, draft, and archived summary cards render.

Expected:

- A guest is redirected to authentication or denied according to the existing Admin route policy.
- An ordinary user receives a denial and cannot create, edit, preview, publish, unpublish, or archive a guide.
- The Admin manager sees the navigation item and management page.
- Hiding the navigation item never replaces server-side authorization; direct URLs remain protected.

### OBG-ADM-02 - Create a draft and store a private video

1. As Admin, open **Onboarding Guides > Create guide**.
2. Enter a title and description.
3. Select `Learner` as the target role.
4. Select a valid playable MP4/WebM.
5. Confirm the selected filename and local preview appear before submitting.
6. Save the draft.
7. Open the resulting Edit page.
8. Confirm the guide is Draft, has Version 1, shows the title/description, and shows the current video metadata.
9. Open the Admin preview video.

Expected:

- The draft is created once and the Admin is redirected to its Edit page.
- The video is stored on the private local disk and is not exposed as a public storage URL.
- The database stores trusted metadata and a private reference, not the binary itself.
- The draft is not visible on a Learner or Instructor dashboard.
- The Admin can preview the current draft while an ordinary user cannot.

### OBG-ADM-03 - Draft validation and upload boundaries

Run each invalid attempt separately from a clean Create form:

1. Submit without a title, description, target role, or video.
2. Submit a text file or a video with an unsupported MIME/extension.
3. Submit a file larger than 100 MiB.
4. If available, submit a file exactly at 100 MiB and one byte over the boundary.
5. Select a file, trigger a validation failure, and inspect the form again.
6. Replace a draft video with a valid file, then simulate a failed persistence/upload request if the environment supports request failure injection.

Expected:

- Validation errors appear beside the owning field and preserve safe text input.
- Accepted formats are MP4, MPEG, MOV, AVI, and WebM.
- Exactly 100 MiB / 102,400 KiB is accepted when the server and client limits permit it; the next byte is rejected.
- Invalid attempts do not create a new guide or version.
- Failed uploads do not leave an abandoned file.
- A failed replacement leaves the previous usable draft video and metadata intact.

### OBG-ADM-04 - Initial publication and role locking

1. From a valid Learner draft, click **Publish guide**.
2. Return to the index and Edit page.
3. Attempt to change the target role after publication.
4. Sign in as an eligible Learner and open the Learner dashboard.
5. Sign in as an eligible Instructor and open the Instructor dashboard without an Instructor guide published.

Expected:

- The guide becomes Published and Version 1 is current.
- Initial publication makes completion required for the initial version.
- The target role is locked after publication and a tampered role update is rejected.
- The eligible Learner can resolve the Learner guide.
- The Instructor does not receive a Learner-targeted guide.
- A published guide is the only guide version available to ordinary users.

### OBG-ADM-05 - Revision, informational update, and supersession

1. As Admin, start a revision of the published Learner guide.
2. Change only the title/description and save the draft.
3. Confirm a new unpublished version exists and the published version remains current.
4. Publish it as an **Informational update**.
5. Complete Version 2 as one eligible Learner.
6. Start another revision and publish it with **Require users to complete this new version**.
7. Open the guide history after each publication.
8. Create a separate draft for the same `dashboard_navigation` key and same target role.
9. Attempt to publish the second guide without supersession confirmation, then publish it with the explicit confirmation.

Expected:

- Published versions are immutable.
- A revision creates one new version and keeps the prior version/history.
- An informational revision preserves effective prior completion/dismissal.
- A required revision starts a new completion boundary without copying state rows.
- Publishing a separate guide for the same key/role requires explicit supersession confirmation.
- Only one guide is Published/current for a key/role pair after supersession.
- The superseded guide becomes Draft; its versions, files, and state rows remain.

### OBG-ADM-06 - Unpublish, republish, and archive preservation

1. Select a currently published guide with at least one version and one test-user state.
2. Click **Unpublish**.
3. Reload the index, Edit page, and History page.
4. Confirm the guide is Draft and the same version remains present.
5. Publish the guide again without selecting a revision-only recompletion option.
6. Confirm that republish succeeds and does not create a new version.
7. Open History and verify the prior direct completion/dismissal counts remain.
8. Archive the guide and accept the confirmation.
9. Try to access the archived guide from an ordinary Learner/Instructor session.

Expected:

- Unpublish removes ordinary-user availability but does not delete the current version, file, or state rows.
- Republish of the same version succeeds without a new version and without requiring a revision-only field.
- Archive retains the guide, versions, file references, and state history.
- Archived guides cannot be published or used by ordinary users.
- The Admin index marks the guide Archived and the aggregate audience card is no longer calculated as Published.

### OBG-MEDIA-01 - Private playback, headers, and seeking

1. Publish a valid guide for the target role.
2. As the eligible target user, open the dashboard modal and start playback.
3. Seek forward and backward in the video.
4. In browser DevTools, inspect the video request and response.
5. Request the same version as a guest.
6. Request a draft, historical, archived, or wrong-role version as an ordinary authenticated user.
7. Request a nonexistent or missing-media version.

Expected:

- The eligible user receives inline private playback.
- The response uses the stored allowed MIME type and includes `Content-Disposition: inline`.
- The response includes `X-Content-Type-Options: nosniff` and private no-store caching.
- Seeking produces normal byte-range behavior, including a `206` response when the client sends a range request.
- Guests and unauthorized roles cannot stream the file.
- Draft, historical, archived, stale, wrong-role, and missing files return a generic denial/not-found result without exposing a storage path.
- Missing media leaves the guide state and publication state unchanged.

### OBG-LEARN-01 - First Learner dashboard presentation

1. Publish a current Learner guide.
2. Use a clean browser session and sign in as an eligible Learner who has no state row for the current version.
3. Open the Learner dashboard.
4. Confirm the modal appears once the dashboard is ready.
5. Confirm the modal title and description match the published version.
6. Confirm the video is not autoplaying with sound.
7. Click **Start Watching**.
8. Reload without choosing Skip or Complete.

Expected:

- The modal is presented for the current eligible Learner version.
- The modal includes the authorized video, Start Watching, Mark guide complete, Skip for now, and an accessible close button.
- Start Watching only starts playback; it does not create a completion state.
- Reloading while incomplete presents the modal again.
- The dashboard remains usable if the video cannot play.

### OBG-LEARN-02 - Learner dismissal paths

Run each path with a fresh eligible Learner or a new required version:

1. Click **Skip for now**.
2. Repeat with the close button.
3. Repeat with the Escape key.
4. Reload the dashboard after each path.
5. Open the Learner Guide page after each path.

Expected:

- All three paths close the modal immediately and persist one dismissal state for the authenticated user/current version.
- `dismissed_at` is set and `completed_at` remains empty.
- The modal does not return for that effective completion boundary.
- The Guide page remains available and offers the user a path to complete the guide.
- A dismissal request failure does not block dashboard access; the user receives a warning that the guide may appear again.

### OBG-LEARN-03 - Learner explicit completion

1. Use a fresh eligible Learner with an incomplete current guide.
2. Open the dashboard modal.
3. Click **Mark guide complete** without starting playback.
4. Confirm the modal closes only after the request succeeds.
5. Reload the dashboard.
6. Open the Learner Guide page.
7. Click or submit completion again if the page still exposes an action.

Expected:

- Completion is explicit and does not depend on playback or video-ended events.
- The modal closes after a successful response; it remains usable with a retryable error if completion fails.
- Reloading does not present the completed current guide again.
- The Guide page displays the completed status and effective completion date.
- Repeating completion is idempotent: no duplicate state row is created and the original completion remains authoritative.

### OBG-INST-01 - Instructor context and precedence

1. Publish an Instructor guide and a separate Learner guide.
2. Sign in as an eligible Instructor and open `/instructor/dashboard`.
3. Confirm the Instructor modal contains only the Instructor guide title, description, and video.
4. Open `/instructor/guide` and complete the guide.
5. Open `/learn/guide` as the same account only if the account is legitimately allowed to switch to Learner context.
6. Sign in as a normal Learner and confirm the Instructor guide is never presented.

Expected:

- Instructor dashboard context resolves the Instructor guide, not the Learner guide.
- The fixed Instructor Guide page shows the same current Instructor version and its state.
- A legitimate learner-context switch uses the expected fixed Learner route and state boundary.
- A normal Learner cannot access the Instructor guide or its private video by changing a URL or version ID.

### OBG-GUIDE-01 - Permanent Guide pages and empty state

1. As an eligible Learner, click the sidebar **Guide** item.
2. Confirm the page shows the current title, description, video, and completion status.
3. Replay the video and complete or dismiss from the page if an action is available.
4. As an eligible Instructor, repeat for the Instructor Guide item.
5. Unpublish or archive the applicable current guide as Admin.
6. Reload the corresponding Guide page.
7. Manually request a historical, draft, archived, or wrong-role version URL.

Expected:

- Learner and Instructor navigation each contain the correct Guide link.
- The page resolves only the current published guide for its fixed role.
- Replaying does not clear, duplicate, or rewrite completion.
- When no current guide exists, the page shows a concise empty state rather than a server error.
- Historical, draft, archived, and wrong-role URLs do not expose guide content or private media.

### OBG-STATE-01 - State isolation, idempotency, and stale requests

1. Complete the same current version twice from the same user/session.
2. Dismiss a version after it is already completed.
3. Complete a version after it was dismissed but before a required revision.
4. Sign in as a second user and confirm the first user's state does not suppress the guide for the second user.
5. Publish a required revision and submit a stale completion/dismissal request for the old version.
6. Attempt to submit a state request while authenticated as a user who cannot access the version's role.

Expected:

- The user/version uniqueness constraint prevents duplicate state rows.
- Completion takes precedence over a later dismissal attempt.
- A dismissed incomplete user can later explicitly complete the current version.
- User A's state never changes User B's presentation.
- A stale or wrong-role state mutation is rejected without changing history.
- Every mutation is evaluated against the authenticated acting user; forged user IDs are ignored/rejected.

### OBG-VERSION-01 - Effective state across informational and required revisions

1. Publish Version 1 with recompletion required.
2. Complete Version 1 as Learner A and dismiss Version 1 as Learner B.
3. Publish Version 2 as an informational update.
4. Reload both users' dashboards and Guide pages.
5. Publish Version 3 as a required revision.
6. Reload both users' dashboards and Guide pages again.
7. Open Admin History and compare direct rows and effective statuses.

Expected:

- Version 2 inherits the effective completion/dismissal boundary without copying state rows.
- Learner A remains effectively completed and Learner B remains effectively dismissed/incomplete for the informational update.
- Version 3 starts a new effective boundary for both users.
- Both users can receive the new required guide again.
- Earlier versions, files, direct state rows, and publication metadata remain visible in history.

### OBG-ANALYTICS-01 - Aggregate audience analytics

1. Prepare at least four currently eligible users for one published role guide:
   - one completed;
   - one dismissed without completion;
   - one untouched;
   - one completed on an earlier effective version.
2. Add at least one ineligible, inactive, suspended, or wrong-role user with no state.
3. Open the Admin guide index.
4. Open the guide History page.
5. Change one user's eligibility condition and reload the Admin index.

Expected:

- The index shows eligible, completed, and dismissed aggregate counts for the current published version.
- `incomplete` includes untouched and dismissed users who have not completed the effective boundary.
- Ineligible users are excluded from eligible and completion percentages.
- Informational revision inheritance is reflected in effective analytics.
- Required revisions reset the effective boundary without deleting old direct counts.
- No named user roster, private evidence, or export is shown.

### OBG-FAIL-01 - Recovery and failure handling

Exercise each failure if the environment can safely simulate it:

1. Remove or make the draft video unreadable before publication.
2. Fail a draft upload or replacement request after the file is selected.
3. Make the completion endpoint return a non-2xx response.
4. Make the dismissal endpoint return a non-2xx response.
5. Interrupt a video request or return a missing media response.
6. Submit publish/unpublish/archive from two browser tabs at nearly the same time.

Expected:

- A missing/unreadable video prevents publication and leaves the previous publication unchanged.
- Failed upload persistence cleans only the newly attempted file and leaves the prior draft usable.
- Completion failure keeps the modal open with an actionable retry message; it does not claim completion.
- Dismissal failure does not block dashboard access and explains that the modal may return.
- Playback failure leaves close/skip/Guide navigation usable.
- Repeated lifecycle actions are idempotent or return a clear conflict; they do not create duplicate versions or delete history.

### OBG-A11Y-01 - Keyboard, focus, and responsive behavior

1. Open a first-presentation modal at desktop width.
2. Navigate every interactive control with Tab and Shift+Tab.
3. Confirm focus stays inside the open dialog and the close button has an accessible name.
4. Press Escape and confirm the same dismissal path is used.
5. Reopen the modal and verify focus returns to the triggering/dashboard context after close.
6. Repeat at 375px and 320px viewport widths.
7. Test with reduced motion and high contrast settings if available.
8. Use a screen reader or accessibility inspector to inspect dialog name, description, video label, button names, and error announcement.

Expected:

- Background controls cannot be accidentally activated while the modal is open.
- Visible focus indicators are present for every keyboard action.
- Escape, close, and skip have the same persisted dismissal semantics.
- Text, buttons, video, and error messages fit without horizontal overflow.
- The layout remains usable on small screens and does not force browser video fullscreen.
- Reduced-motion settings do not make the modal unusable.

## 7. Deployment and release checks

These checks require the actual target environment and must not be inferred from local PHPUnit or source inspection.

### OBG-DEPLOY-01 - Staging migration and permission

1. Back up or confirm the target environment's normal recovery process.
2. Run the normal forward-only migration command on staging.
3. Confirm both onboarding migrations report `Ran`.
4. Confirm the `manage onboarding guides` permission exists exactly once and Admin has it.
5. Confirm existing users and role assignments are unchanged.

Expected:

- No reset, wipe, truncate, drop, recreation, or destructive reseed is used.
- Existing data remains available.
- The Admin navigation and routes work after deployment.

### OBG-DEPLOY-02 - Asset and server upload limits

1. Run the production asset build and confirm the deployed manifest references the current bundle.
2. Confirm PHP `upload_max_filesize` is at least 100M.
3. Confirm PHP `post_max_size` is at least 110M.
4. Upload a valid near-boundary video through the real Admin UI.
5. Confirm the target private disk is writable by the application and not publicly browsable.

Expected:

- The deployed bundle contains the onboarding modal registration and state actions.
- Valid accepted videos reach Laravel validation instead of being rejected by the web server first.
- Private files are served through the authorized controller only.

### OBG-DEPLOY-03 - Production private playback

1. Publish a non-sensitive production/staging guide using the approved operational process.
2. Test playback as the correct Learner and Instructor roles.
3. Test the same video as a guest, wrong role, and ordinary account.
4. Seek through the video and inspect range responses.
5. Inspect response headers and browser caching behavior.

Expected:

- Authorized playback works inline and seeking works.
- Unauthorized requests do not expose the file or storage path.
- `Content-Disposition` is inline, MIME is correct, `X-Content-Type-Options` is `nosniff`, and caching is private/no-store.
- No public storage URL or directory listing is available.

## 8. Manual result template

Copy this block for each test case:

```text
Test ID:
Date/time:
Environment and URL:
Branch/build identifier:
Persona/account (safe label only):
Guide ID / role / version:
Preconditions:

Steps executed:
1.
2.
3.

Observed result:

Expected result:

Pass / Fail / Blocked:
Evidence reference (safe screenshot, response status, or guide/version ID):
Defect or follow-up:
```

## 9. Sign-off boundary

The onboarding guides feature is ready for release consideration only when:

- focused automated tests are green;
- normal migrations are applied in the target environment;
- Admin lifecycle, private media, Learner, Instructor, state, version, analytics, accessibility, and failure cases above are manually recorded as passed or have an approved defect disposition;
- the production asset build is current;
- effective PHP upload limits are confirmed;
- private playback and range behavior are verified in the target deployment;
- no destructive database operation was used;
- remaining evidence is clearly labeled as local, staging, or production.
