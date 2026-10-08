# Support Workflow Refinement Implementation Plan

> **Required sub-skill:** Use `superpowers:executing-plans` to implement this plan task by task, `superpowers:test-driven-development` for every behavior change, `superpowers:systematic-debugging` for unexpected failures, and `superpowers:verification-before-completion` before reporting completion.

**Goal:** Implement the approved Help Center image delivery, private Platform Feedback ticket workflow, and consent-controlled Testimonial refinements end to end for learner, instructor, connector, administrator, and guest contexts.

**Architecture:** Keep the three domains separate. Platform Feedback gains a transactional conversation layer around its existing lifecycle service; Help Center images gain authorization-aware Laravel delivery from persisted section paths; Testimonials gain a server-side public-name resolver and stricter consent/publication rules. Existing Blade layouts, route contexts, policies, database notifications, toast helpers, and SweetAlert confirmation hooks remain the integration points.

**Tech stack:** Laravel 12, PHP 8.2+, Eloquent, Blade, Alpine.js, Tailwind CSS, database notifications, PHPUnit 11, Vite.

**Approved specification:** `docs/superpowers/specs/2026-09-14-support-workflow-refinement-design.md`

## Global constraints

- Work in the current primary checkout on branch `community-feed-v1`; do not create a worktree or switch branches.
- Preserve every unrelated dirty or untracked file. Inspect scoped diffs before and after each task.
- Do not commit, push, merge, or create a pull request unless the user separately requests it. Checkpoints below replace commit steps.
- Do not merge Platform Feedback with testimonials, module/instructor reviews, Community Hub reports, moderation cases, content reports, chat reports, or urgent safety reporting.
- Never expose tickets, ticket messages, ticket attachments, restricted Help media, or historical internal notes publicly.
- Use the existing global layout flash-to-toast behavior for ordinary successful saves. Use `data-confirm-submit` / `window.ccSweetAlert` only for destructive or public-visibility actions.
- Keep connector feedback records user-owned and global; connector routes are authorized navigation/shell context only.
- All mutable workflows must enforce policy and lifecycle rules server-side; hidden buttons are not authorization.
- Run focused tests first. Treat environmental failures separately from assertion failures and never describe a blocked suite as passing.

## Task 1: Normalize the ticket status domain and migration

**Files:**

- Modify: `app/Enums/PlatformFeedbackStatus.php`
- Modify: `app/Services/Support/PlatformFeedbackLifecycleService.php`
- Modify: `app/Services/Support/PlatformFeedbackInsights.php`
- Modify: `database/factories/PlatformFeedbackFactory.php`
- Create: `database/migrations/2026_09_14_000001_normalize_platform_feedback_statuses.php`
- Modify: `tests/Unit/Support/SupportEnumTest.php`
- Modify: `tests/Feature/Support/AdminPlatformFeedbackTest.php`
- Modify: `tests/Feature/Support/PlatformFeedbackSchemaMigrationTest.php`

### Step 1: Write failing enum and transition tests

Assert that the persisted values are exactly `new`, `reviewed`, `resolved`, `closed`, and `withdrawn`; that `reviewed` labels as `In Review`; that Closed and Withdrawn are terminal; and that Resolved may reopen only to Reviewed.

```php
$this->assertSame(
    ['new', 'reviewed', 'resolved', 'closed', 'withdrawn'],
    PlatformFeedbackStatus::values(),
);
$this->assertSame('In Review', PlatformFeedbackStatus::Reviewed->label());
```

Add lifecycle assertions for New -> Reviewed, Reviewed -> Resolved, Reviewed -> Closed, and Resolved -> Reviewed. Add rejection assertions for Closed -> Reviewed, Withdrawn -> Reviewed, Reviewed -> Withdrawn, and direct New -> Resolved through the low-level transition method.

### Step 2: Run the focused tests and confirm failure

```powershell
php artisan test tests/Unit/Support/SupportEnumTest.php tests/Feature/Support/AdminPlatformFeedbackTest.php
```

Expected: failures mention the obsolete Planned/Archived cases and current transition map.

### Step 3: Replace the enum and transition map

Use this canonical enum surface:

```php
enum PlatformFeedbackStatus: string
{
    case New = 'new';
    case Reviewed = 'reviewed';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Withdrawn = 'withdrawn';
}
```

Update `PlatformFeedbackLifecycleService::ALLOWED` so ordinary admin transitions are `new => reviewed|closed`, `reviewed => resolved|closed`, and `resolved => reviewed|closed`. Owner withdrawal remains a separate owner-only operation. On the first New -> Reviewed transition, set `reviewed_by` and `reviewed_at`; set `resolved_at` on resolution and clear it on reopening. Create a history row only for a real status transition.

The admin-message precondition for Resolved lands with the message relation in Task 3, so this task remains independently green. Here, test only the canonical transition graph and direct New -> Resolved rejection.

### Step 4: Add the forward-only data migration

In `up()`, update `platform_feedback.status` and both `platform_feedback_histories.from_status`/`to_status`:

```php
DB::table('platform_feedback')->where('status', 'planned')->update(['status' => 'reviewed']);
DB::table('platform_feedback')->where('status', 'archived')->update(['status' => 'closed']);
DB::table('platform_feedback_histories')->where('from_status', 'planned')->update(['from_status' => 'reviewed']);
DB::table('platform_feedback_histories')->where('to_status', 'planned')->update(['to_status' => 'reviewed']);
DB::table('platform_feedback_histories')->where('from_status', 'archived')->update(['from_status' => 'closed']);
DB::table('platform_feedback_histories')->where('to_status', 'archived')->update(['to_status' => 'closed']);
```

Make `down()` non-destructive: it must not guess which canonical rows originated from legacy values. Document that choice in the migration.

Add a schema migration test that inserts legacy rows before executing the migration method and asserts the normalized ticket and history values.

### Step 5: Remove obsolete analytics/factory references and rerun

Update unresolved counts to mean New + Reviewed. Remove Planned/Archived factory states or references.

```powershell
php artisan test tests/Unit/Support/SupportEnumTest.php tests/Feature/Support/PlatformFeedbackSchemaMigrationTest.php tests/Feature/Support/AdminPlatformFeedbackTest.php
vendor\bin\pint --dirty
git diff --check
```

Checkpoint: inspect only the listed files with `git diff -- <paths>` and retain all unrelated changes.

## Task 2: Refine owner ticket submission, filters, rating, and withdrawal

**Files:**

- Modify: `app/Http/Requests/StorePlatformFeedbackRequest.php`
- Modify: `app/Http/Controllers/PlatformFeedbackController.php`
- Modify: `app/Policies/PlatformFeedbackPolicy.php`
- Modify: `app/Services/Support/SupportRouteContext.php`
- Modify: `resources/views/feedback/create.blade.php`
- Modify: `resources/views/feedback/index.blade.php`
- Modify: `resources/views/feedback/show.blade.php`
- Modify: `tests/Feature/Support/PlatformFeedbackValidationTest.php`
- Modify: `tests/Feature/Support/PlatformFeedbackUiTest.php`
- Modify: `tests/Feature/Support/SupportTicketSeparationTest.php`

### Step 1: Write failing request and UI tests

Cover:

- one `<select name="type">` with `general` / General Inquiry, `bug_report` / Report a Problem, `feature_suggestion` / Suggestion, `accessibility_issue` / Technical Issue, `help_content_issue` / Content Concern, and `account_payment_issue` / Account or Payment Issue;
- recognized query-string type selection and safe fallback to General Inquiry;
- description accepts 500 characters and rejects 501;
- optional ratings 1–5 persist, invalid values fail, and no rating is valid;
- five radio inputs have accessible heart labels and cumulative-fill hooks;
- owner filters `all`, `active`, `resolved`, and `closed` have exact membership;
- only the ticket owner may withdraw, and only while New;
- admins and other users receive 403 from the owner withdrawal endpoint;
- connector routes show the same authenticated user's tickets but reject non-members.

### Step 2: Run the focused tests and confirm failure

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackValidationTest.php tests/Feature/Support/PlatformFeedbackUiTest.php tests/Feature/Support/SupportTicketSeparationTest.php
```

### Step 3: Implement the dropdown, limit, and heart radios

Change server validation to `max:500`. Replace the type cards with a labeled native select populated from `PlatformFeedbackType::cases()`. Keep the current allowlisted prefilling in the controller.

Render heart radios as a fieldset; each input remains focusable and each label says `N out of 5 hearts`. Alpine state may control cumulative purple fill, but the submitted value remains the integer radio value. The counter must initialize from `old('description')` and display `current/500`.

### Step 4: Implement exact filter semantics

Allow only `active`, `resolved`, and `closed` besides the default `all`:

```php
'active' => [PlatformFeedbackStatus::New, PlatformFeedbackStatus::Reviewed],
'resolved' => [PlatformFeedbackStatus::Resolved],
'closed' => [PlatformFeedbackStatus::Closed, PlatformFeedbackStatus::Withdrawn],
```

Update tabs, counts/empty copy, and connector route parameters without persisting a connector ID.

### Step 5: Enforce New-only owner withdrawal

Make `PlatformFeedbackPolicy::withdraw()` require both owner identity and New status. Keep the controller's explicit status guard, and have the controller call the lifecycle service or a transaction that produces exactly one New -> Withdrawn history entry. Do not let administrators use this endpoint.

Use `data-confirm-submit` for the visible Withdraw form. Hide it for every non-New status.

### Step 6: Rerun focused tests and formatting

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackValidationTest.php tests/Feature/Support/PlatformFeedbackUiTest.php tests/Feature/Support/SupportTicketSeparationTest.php
vendor\bin\pint --dirty
git diff --check
```

## Task 3: Add the private two-way ticket conversation

**Files:**

- Create: `database/migrations/2026_09_14_000002_create_platform_feedback_messages_table.php`
- Create: `database/migrations/2026_09_14_000003_migrate_legacy_platform_feedback_responses.php`
- Create: `app/Models/PlatformFeedbackMessage.php`
- Create: `database/factories/PlatformFeedbackMessageFactory.php`
- Create: `app/Http/Requests/StorePlatformFeedbackMessageRequest.php`
- Create: `app/Http/Controllers/PlatformFeedbackMessageController.php`
- Create: `app/Services/Support/PlatformFeedbackConversationService.php`
- Create: `app/Notifications/PlatformFeedbackMessageNotification.php`
- Modify: `app/Models/PlatformFeedback.php`
- Modify: `app/Services/Support/SupportRouteContext.php`
- Modify: `app/Services/Chat/SupportAdminResolver.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/feedback/show.blade.php`
- Modify: `resources/views/admin/feedback/show.blade.php`
- Create: `resources/views/components/support/ticket-conversation.blade.php`
- Create: `tests/Feature/Support/PlatformFeedbackConversationTest.php`
- Modify: `tests/Unit/Support/SupportModelTest.php`

### Step 1: Write failing message-model and authorization tests

Define the new table contract:

```php
$table->id();
$table->foreignId('platform_feedback_id')->constrained()->cascadeOnDelete();
$table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
$table->string('sender_role', 20);
$table->text('body');
$table->timestamps();
$table->index(['platform_feedback_id', 'created_at']);
```

Test cascade behavior, nullable deleted admin sender, chronological relation ordering, escaped rendering, and access by owner/admin only.

Test 1,000 accepted / 1,001 rejected, owner cannot speak before an admin response, owner and unrelated user cannot use admin behavior, admin can reply to New/Reviewed/Resolved, and neither side can reply to Closed/Withdrawn.

### Step 2: Write failing transaction and notification tests

Cover these exact invariants:

- first admin message on New creates Reviewed history + message + one owner notification;
- owner response on Resolved creates Reviewed history + message + one admin notification;
- active reviewer receives owner response; otherwise one active authorized admin from `SupportAdminResolver` receives it;
- message validation/database failure rolls back status/history and sends no notification;
- status-only updates retain the existing `PlatformFeedbackUpdatedNotification` behavior;
- messages never become public and connector routes cannot reveal another owner's thread.

Use `Notification::fake()` and assert one recipient notification, not merely that a notification type exists.

### Step 3: Run the focused tests and confirm failure

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackConversationTest.php tests/Unit/Support/SupportModelTest.php
```

### Step 4: Implement the model, relation, validation, and legacy migration

`PlatformFeedbackMessage` must allow only server-supplied foreign keys/role/body, cast no HTML, and expose `ticket()` and `sender()` relations. Add `PlatformFeedback::messages()` ordered oldest first.

The request validates only:

```php
return ['body' => ['required', 'string', 'max:1000']];
```

Derive `sender_role` on the server from the authenticated user's effective role and allow only `learner`, `parent`, `instructor`, `connector`, or `admin`.

The legacy migration selects non-empty `staff_response` records and inserts one message per ticket, using `reviewed_by`, `reviewed_at ?? updated_at`, and the role `admin`. Make it idempotent by checking for an existing legacy-equivalent message for the ticket before insertion. Keep `staff_response` and history columns intact.

### Step 5: Implement one transactional conversation service

Give `PlatformFeedbackConversationService` one public method:

```php
public function send(PlatformFeedback $ticket, User $sender, string $body): PlatformFeedbackMessage
```

Within one `DB::transaction()`:

- authorize/branch by owner versus admin;
- admin + New: transition to Reviewed before inserting;
- owner: require an existing admin-role message;
- owner + Resolved: transition to Reviewed before inserting;
- reject Closed/Withdrawn;
- create exactly one message.

Dispatch exactly one `PlatformFeedbackMessageNotification` only after the transaction succeeds. Owner recipients use canonical `feedback.show`; admin recipients use `admin.feedback.show`. Keep optional connector navigation context in the form action/view while database notifications use canonical URLs.

If `SupportAdminResolver` currently accepts chat-only permissions, narrow or add a support-ticket resolver query that guarantees the fallback user is active and authorized for the admin feedback page.

### Step 6: Add POST routes and shared conversation UI

Add throttled, CSRF-protected routes:

```php
Route::post('/feedback/submissions/{platformFeedback}/messages', [PlatformFeedbackMessageController::class, 'store'])
    ->middleware('throttle:30,1')->name('feedback.messages.store');
Route::post('/connector/{connector}/feedback/submissions/{platformFeedback}/messages', [PlatformFeedbackMessageController::class, 'store'])
    ->middleware('throttle:30,1')->name('connector.feedback.messages.store');
Route::post('/admin/feedback/{platformFeedback}/messages', [PlatformFeedbackMessageController::class, 'store'])
    ->middleware('throttle:30,1')->name('admin.feedback.messages.store');
```

Add `message_store` to `SupportRouteContext`. Load `messages.sender` in both show controllers. The component renders the immutable original submission first, then chronological bubbles with sender name, snapshotted role, timestamp, preserved line breaks, and a composer only when permitted. Add an accessible `current/1,000` counter and disabled submitting state. Do not render `staff_response` separately.

### Step 7: Rerun focused tests and inspect routes

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackConversationTest.php tests/Feature/Support/SupportTicketSeparationTest.php tests/Unit/Support/SupportModelTest.php
php artisan route:list --name=feedback --except-vendor
vendor\bin\pint --dirty
git diff --check
```

Confirm every new route name is unique before later route-cache verification.

## Task 4: Make admin review stateful, concise, and toast-consistent

**Files:**

- Modify: `app/Http/Controllers/Admin/PlatformFeedbackController.php`
- Modify: `app/Http/Requests/Admin/UpdatePlatformFeedbackRequest.php`
- Modify: `app/Models/PlatformFeedback.php`
- Modify: `app/Models/PlatformFeedbackHistory.php`
- Modify: `resources/views/admin/feedback/index.blade.php`
- Modify: `resources/views/admin/feedback/show.blade.php`
- Modify: `resources/views/components/support/status-badge.blade.php`
- Modify: `routes/admin.php`
- Modify: `tests/Feature/Support/AdminPlatformFeedbackTest.php`
- Modify: `tests/Feature/Support/PlatformFeedbackUiTest.php`

### Step 1: Write failing eye/open and review-update tests

Test that:

- the inbox action is one accessible eye-only POST form;
- POST `admin.feedback.open` changes New to Reviewed and creates one history row;
- opening again only redirects and creates no duplicate history;
- GET `admin.feedback.show` never mutates state;
- status controls omit Planned and Archived;
- Resolved is unavailable until an admin message exists, including crafted requests;
- New -> Resolved records New -> Reviewed -> Resolved atomically only when an admin message exists;
- Close uses confirmation and is terminal;
- the page contains no `internal_note`, `staff_response`, local success banner, or old progress explanation;
- successful update flashes `Support ticket updated.` for the global toast.

### Step 2: Run the focused tests and confirm failure

```powershell
php artisan test tests/Feature/Support/AdminPlatformFeedbackTest.php tests/Feature/Support/PlatformFeedbackUiTest.php
```

### Step 3: Add the explicit open action

Add:

```php
public function open(Request $request, PlatformFeedback $platformFeedback, PlatformFeedbackLifecycleService $lifecycle): RedirectResponse
```

Authorize admin update. If and only if status is New, transition to Reviewed. Redirect to `admin.feedback.show`. Replace the GET eye link with a POST form containing `@csrf`, an eye icon, focus styles, tooltip/title, and screen-reader label.

### Step 4: Restrict update input and make compound transitions atomic

`UpdatePlatformFeedbackRequest` accepts only canonical `status`. Remove `internal_note` and `staff_response` validation. Remove both fields from `PlatformFeedback::$fillable`; remove them from new history writes and from `PlatformFeedbackHistory::$fillable` while preserving database columns.

Wrap any two-step New -> Reviewed -> Resolved operation in one outer `DB::transaction()`. Require an existing admin message before the transaction starts and re-check inside the lifecycle service. Send no notification until the whole transaction succeeds.

### Step 5: Refine the review layout

Main column: sender profile, reference/type/date, heart rating, description, authorized attachment preview, shared conversation, and transition-only history. Sidebar: current status, allowed next status, message/status actions. Remove private-note UI and legacy response textarea. Treat Resolved as reopenable and Closed as final. Use `data-confirm-submit` only for Close/public destructive forms.

Remove the page-local flash banner so the containing admin layout emits the single existing top-right toast.

### Step 6: Rerun tests

```powershell
php artisan test tests/Feature/Support/AdminPlatformFeedbackTest.php tests/Feature/Support/PlatformFeedbackConversationTest.php tests/Feature/Support/PlatformFeedbackUiTest.php
vendor\bin\pint --dirty
git diff --check
```

## Task 5: Deliver Help article images safely and modernize section uploads

**Files:**

- Create: `app/Http/Controllers/HelpArticleSectionImageController.php`
- Create: `app/Services/Support/HelpArticleVisibilityService.php`
- Modify: `app/Http/Controllers/HelpCenterController.php`
- Modify: `app/Http/Requests/Admin/StoreHelpArticleRequest.php`
- Modify: `app/Http/Requests/Admin/UpdateHelpArticleRequest.php`
- Modify: `app/Services/Support/HelpArticlePersistenceService.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/help/articles/form.blade.php`
- Modify: `resources/views/admin/help/articles/preview.blade.php`
- Modify: `resources/views/help/show.blade.php`
- Modify: `tests/Feature/Support/AdminHelpCenterTest.php`
- Modify: `tests/Feature/Support/PublicHelpCenterTest.php`
- Modify: `tests/Feature/Support/RoleAwareHelpCenterTest.php`
- Modify: `tests/Feature/Support/HelpCenterExperienceTest.php`

### Step 1: Write failing media authorization and cache tests

Cover public, authenticated audience-restricted, connector, and admin routes. Assert:

- section must belong to the routed article;
- guest receives an image only when the article/category are published, active, and guest-visible;
- learner/instructor/connector see only matching audience media;
- connector route checks workspace membership;
- admin can view Draft/Published/Archived media;
- missing section/file returns 404 without a storage path;
- response is inline with detected MIME;
- genuinely guest-visible media has public cache headers;
- restricted and admin draft/archive media has `private, no-store`.

Use `Storage::fake('public')` and real small decoded JPG/PNG/WebP fixtures accepted by `ValidSupportImage` where upload validation is tested.

### Step 2: Write failing generated-alt and picker tests

Assert manual `image_alt_text` is absent, not required, and ignored if crafted. On any save of a section containing an image, assert exact generated text:

```php
'Screenshot for '.($sectionHeading ?: $articleTitle)
```

Assert existing untouched records keep their stored alt until saved; after save the deterministic text overwrites it. Assert edit/public/admin preview URLs point to named Laravel image routes, never `Storage::url()` or the `/storage/help/articles/` URL prefix.

Test the picker markup exposes JPG/PNG/WebP and 5 MB guidance, filename, preview image, and independent remove controls per repeated section.

### Step 3: Run the focused tests and confirm failure

```powershell
php artisan test tests/Feature/Support/AdminHelpCenterTest.php tests/Feature/Support/PublicHelpCenterTest.php tests/Feature/Support/RoleAwareHelpCenterTest.php tests/Feature/Support/HelpCenterExperienceTest.php
```

### Step 4: Centralize article visibility and add image routes

Move the public article lookup rule into `HelpArticleVisibilityService`, with a method that receives the request user, optional connector, and route-bound article and returns the visible article or 404. Use it from both `HelpCenterController::show()` and the public/connector image action so rules cannot drift.

Add named routes for public, connector, and admin section images. In the controller:

```php
abort_unless((int) $section->help_article_id === (int) $article->id, 404);
abort_unless($section->image_path && Storage::disk('public')->exists($section->image_path), 404);
return Storage::disk('public')->response(
    $section->image_path,
    null,
    ['Content-Disposition' => 'inline', 'Cache-Control' => $cacheControl],
);
```

Determine MIME from the configured disk/file response. Do not accept a path query/body parameter. Apply connector membership before visibility. Admin media routes use admin middleware and skip publication filtering only after confirming section ownership.

### Step 5: Generate alt text server-side

Remove `sections.*.image_alt_text` from validation and all form inputs. In `HelpArticlePersistenceService`, whenever the saved section has an image path, compute the deterministic alt from the submitted heading or article title; set null when the image is removed. Keep obsolete-file deletion after successful database commit and new-file cleanup on exceptions.

### Step 6: Implement the repeated modern picker

Reuse the ticket picker's visual pattern in each repeatable article section while maintaining per-section Alpine state. Existing image, selected image, remove flag, filename, and preview URL must not leak into another section. Revoke object URLs when replaced/removed. Keep the native file input keyboard-accessible with `accept="image/jpeg,image/png,image/webp"`.

Replace all direct public-storage image URLs with the named route for the current context.

### Step 7: Rerun tests and confirm article save toast contract

```powershell
php artisan test tests/Feature/Support/AdminHelpCenterTest.php tests/Feature/Support/PublicHelpCenterTest.php tests/Feature/Support/RoleAwareHelpCenterTest.php tests/Feature/Support/HelpCenterExperienceTest.php
php artisan route:list --name=help --except-vendor
vendor\bin\pint --dirty
git diff --check
```

Confirm `HelpArticleController::update()` still redirects with `Help article updated.` and that no duplicate local success banner exists.

## Task 6: Snapshot testimonial identity and enforce revocable publication consent

**Files:**

- Create: `app/Services/Support/TestimonialDisplayNameResolver.php`
- Modify: `app/Http/Controllers/TestimonialController.php`
- Modify: `app/Http/Requests/StoreTestimonialRequest.php`
- Modify: `app/Services/Support/TestimonialSubmissionService.php`
- Modify: `app/Services/Support/TestimonialEligibility.php`
- Modify: `app/Services/Support/TestimonialPublicationService.php`
- Modify: `app/Http/Requests/Admin/UpdateTestimonialRequest.php`
- Modify: `app/Http/Controllers/Admin/TestimonialController.php`
- Modify: `app/Models/Testimonial.php`
- Modify: `app/Policies/TestimonialPolicy.php`
- Modify: `resources/views/testimonials/create.blade.php`
- Modify: `resources/views/testimonials/index.blade.php`
- Modify: `resources/views/testimonials/show.blade.php`
- Modify: `resources/views/admin/testimonials/edit.blade.php`
- Modify: `resources/views/admin/testimonials/preview.blade.php`
- Modify: `resources/views/components/support/testimonial-avatar.blade.php`
- Modify: `resources/views/landing/index.blade.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Support/TestimonialPublicationUiTest.php`
- Create: `tests/Unit/Support/TestimonialDisplayNameResolverTest.php`

### Step 1: Write failing name-resolution tests

Assert:

- adult learner snapshots `learnerProfile.username`;
- instructor snapshots `full_name`, falling back to `users.name`;
- whitespace-only/missing values produce no public name and submission returns a profile-completion validation message;
- posted `display_name` cannot override the resolved value;
- later profile edits do not change an existing snapshot;
- null/active author status is eligible; inactive/suspended/archived is ineligible.

Use one resolver contract:

```php
public function resolve(User $user): ?string
```

### Step 2: Write failing withdrawal/publication tests

Cover owner Draft and Published withdrawal, immediate removal from the landing scope, cleared publication timestamp, revoked consent timestamp, and shared confirmation markup. Assert owner cannot withdraw Rejected/Withdrawn, another user cannot withdraw, and an admin cannot use the owner endpoint.

Keep the admin-specific withdrawal action authorized separately so admin preview retains Preview/Edit/Reject/Withdraw/Publish controls. Assert an owner-withdrawn record cannot be republished, while a new consented record may be submitted.

Assert admin update cannot change `display_name`, including a crafted payload.

### Step 3: Write failing landing and avatar tests

Assert the section is immediately before the footer, titled `What our community says`, hidden with zero eligible records, limited to six, and uses responsive one/two/three-column classes with centered one/two-card layout. Assert quotation-first markup, snapshotted name, optional role, consented photo, and initials fallback. Add an `onerror` assertion that hides a broken image and reveals the already-rendered initials fallback.

### Step 4: Run focused tests and confirm failure

```powershell
php artisan test tests/Unit/Support/TestimonialDisplayNameResolverTest.php tests/Feature/Support/TestimonialPublicationUiTest.php
```

### Step 5: Implement resolved identity and immutable snapshot

Pass the resolved display name to the create view and render it read-only before the consent checkbox. Remove the display-name input and validation rule. In the submission service, resolve again server-side and use this exact failure when it is missing:

```php
throw ValidationException::withMessages([
    'profile' => 'Complete your profile name before submitting a testimonial.',
]);
```

Write only the server-resolved value.

Update the idempotency fallback hash to use the resolved name plus quotation, never client display-name input. Remove `display_name` from `UpdateTestimonialRequest`; render it read-only in admin edit. Keep the database/model field for legacy records and factories, but ensure no owner or admin HTTP request validates or forwards it; `TestimonialSubmissionService` is the only production write path for a new identity snapshot.

### Step 6: Separate owner and admin withdrawal authorization

`TestimonialPolicy::withdraw()` must require owner identity and Draft/Published status. Add an admin-only policy ability for the admin withdrawal endpoint. Owner withdrawal calls `withdraw($testimonial, true)`; admin withdrawal does not silently impersonate consent revocation.

Keep publish eligibility dependent on active consent, adult learner/instructor eligibility, safe snapshot, quotation, and allowed presentation. Tighten `scopePubliclyVisible()` to accept author status only when null or `User::STATUS_ACTIVE`, excluding inactive, suspended, and archived.

### Step 7: Refine the landing card and image fallback

Keep the query at six, eager-load profile sources, and place the section directly before `<footer>`. Render no sample cards or carousel. Always render initials fallback when profile-image consent is true, place the `<img>` over it when a URL exists, and use a small `onerror` handler to hide the failed image and reveal the fallback. Preserve escaped text and accessible name semantics.

### Step 8: Rerun tests and formatting

```powershell
php artisan test tests/Unit/Support/TestimonialDisplayNameResolverTest.php tests/Feature/Support/TestimonialPublicationUiTest.php
vendor\bin\pint --dirty
git diff --check
```

## Task 7: Cross-role regression, caches, build, and browser QA

**Files:**

- Modify as failures require, within the approved files above only
- Modify: `tests/QA/SupportUiQaTest.php`
- Modify: `tests/QA/SupportQaReviewTest.php` only where assertions are stale because of this approved refinement
- Create: `docs/qa/2026-09-14-support-workflow-refinement/manual-checklist.md`

### Step 1: Add one explicit role/access matrix

The QA tests/checklist must cover:

| Actor | Help media | Own ticket/thread | Other ticket/thread | Submit testimonial | Admin support actions |
|---|---|---|---|---|---|
| Guest | Guest-visible only | No | No | No | No |
| Adult learner | Matching audience | Yes | No | Yes with profile name + consent | No |
| Instructor | Matching audience | Yes | No | Yes with account name + consent | No |
| Connector member | Connector-visible in shell | Yes, global owner data | No | Only if otherwise eligible role | No |
| Admin | Draft/published/archived | Authorized review | Authorized review | No owner submission | Yes |

Also cover minor/parent testimonial rejection and inactive/suspended/archived author exclusion.

### Step 2: Run the complete focused support suite

```powershell
php artisan test tests/Unit/Support tests/Feature/Support
```

Expected: all focused support tests pass. If an older assertion contradicts the approved spec, update only that assertion and record the reason in the checklist.

### Step 3: Run QA tests separately

```powershell
php artisan test tests/QA/SupportUiQaTest.php
php artisan test tests/QA/SupportQaReviewTest.php
```

Do not conceal unrelated historical QA failures. Fix only failures caused by the approved support changes; list pre-existing/out-of-scope blockers separately.

### Step 4: Verify routes, views, formatting, and assets

```powershell
php artisan route:clear
php artisan route:cache
php artisan route:clear
php artisan view:clear
php artisan view:cache
php artisan view:clear
vendor\bin\pint --dirty
npm.cmd run build
git diff --check
```

The route-cache command is mandatory because duplicate route names previously broke serialization. The view-cache command is mandatory because these changes touch repeated Blade/Alpine markup.

### Step 5: Perform browser-level end-to-end checks

Using seeded learner, instructor, connector-member, and admin accounts, record pass/fail in the manual checklist for:

1. Learner creates a 500-character ticket with dropdown, hearts, and image; admin eye opens it; admin replies; learner replies; admin resolves; learner replies and reopens; admin closes; no further messages/withdrawal are available.
2. Instructor completes the same ticket exchange in the instructor shell.
3. Connector member sees the same personal ticket set in connector context without connector data persistence or cross-user leakage.
4. Help article editor selects/previews/removes/replaces independent section images, saves with one toast, and sees the image in admin preview and every authorized public role; unauthorized roles/missing files get 404.
5. Adult learner submits with read-only username; adult instructor submits with resolved account name; admin cannot edit the public-name snapshot; publish displays the card before footer; owner withdrawal removes it immediately.
6. Broken testimonial avatar falls back to initials; no eligible testimonials removes the entire landing section.
7. Close, withdraw, publish, reject, and public-withdraw actions use the shared confirmation; ordinary saves use one global toast and no confirmation.

Capture the tested route, role, result, and any environment blocker. Do not include private ticket text or attachments in QA artifacts.

### Step 6: Final scoped review

```powershell
git status --short
git diff --stat
git diff --check
rg -n "PlatformFeedbackStatus::(Planned|Archived)|internal_note|staff_response" app resources routes tests
rg -n "Storage::disk\('public'\)->url|/storage/help/articles|image_alt_text" app resources routes tests
```

Any remaining `internal_note`, `staff_response`, or `image_alt_text` references must be either preserved migration/schema compatibility or an explicitly justified historical test; none may drive new UI or writes. Any Planned/Archived status reference must exist only in the normalization migration/test fixture.

Report focused test counts, QA test results, route/view cache results, build result, manual matrix result, current branch, and dirty-file preservation. Do not claim a commit, push, PR, or merge.

## Plan self-review checklist

- Every approved specification section maps to at least one task and one verification point.
- Status, message, notification, media, consent, and authorization invariants are enforced server-side.
- Normal save toasts and destructive/public confirmations use existing system helpers.
- New route names are cache-tested for uniqueness.
- No arbitrary storage path is accepted.
- No client-controlled testimonial display name is persisted.
- No owner/admin action is authorized solely by its visible button.
- No task requires a worktree, branch switch, commit, push, PR, or destructive cleanup.
- Commands are PowerShell-compatible for this Windows checkout.
