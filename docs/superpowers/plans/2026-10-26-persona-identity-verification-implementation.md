# Persona Identity Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate Persona Sandbox into the existing learner and guardian identity workflows while retaining local evidence, manual administrator decisions, private access controls, resubmission history, and dependent-verification boundaries.

**Architecture:** Add a small append-only Persona attempt ledger beside the existing learner and guardian records. A server-only client creates and resumes Hosted Flow inquiries; signed webhooks enqueue authoritative API reconciliation; a deny-by-default policy evaluator unlocks the existing local evidence step only for a current eligible attempt. Existing review pages gain a compact Persona summary and transient administrator-only media proxy.

**Tech Stack:** PHP 8.2, Laravel 12, MySQL, PHPUnit 11, Blade, Alpine.js 3, Tailwind CSS 3, Laravel HTTP client, Laravel database queue, Vite 7.

**Design reference:** `docs/superpowers/specs/2026-10-26-persona-identity-verification-design.md`

## Global Constraints

- Preserve all development and identity data. Use one additive migration and ordinary `php artisan migrate` only after inspecting the target schema. Never use `migrate:fresh`, `db:wipe`, `TRUNCATE`, `DROP`, destructive seeders, or the development database as disposable test data.
- Before database tests, confirm `phpunit.xml` points at the isolated `cc_db_test` database. Run database suites serially. Use `--do-not-cache-result` so PHPUnit cannot fail only because its cache file is unwritable.
- Do not edit or print the real `.env`. This repository currently has no `.env.example`, so document variable names in the Sandbox runbook and read them through `config/persona.php`. Never print the API key, webhook secret, resume token, raw Persona payload, or signed media URL.
- Pin every authenticated request and the Dashboard webhook to Persona API version `2026-09-29`.
- This change is Sandbox-only. Reject any configured mode other than `sandbox`. Every user/admin result is labelled simulated. Production enablement is a separate approved change after the design's release gates.
- Existing approved and pending learner/guardian cases are grandfathered. Persona is required for a not-yet-submitted case and each new round after rejection; it never revokes an existing approval or invalidates a pending review.
- Keep Persona state separate from learner/guardian application status. Inquiry creation, Hosted Flow return, webhook receipt, or Persona completion never writes application `approved`.
- Reuse the existing local private-storage, evidence preview, review, rejection, notification, middleware, and dependent-relationship flows. Do not add a Persona SDK, generic provider framework, repository layer, separate dashboard, or frontend framework.
- Persona runs before local upload. Teen school/institution routes retain only the local ID and use the proxied Persona selfie for manual comparison. Government-ID learner routes retain the existing local government ID and local selfie. Guardians retain the existing local government ID and gain no new local selfie.
- Government-ID policy fails closed. Accept only an exact configured application-to-Persona class mapping, issuing country `PH`, passed required resources, a current attempt, and the correct target submission round. Do not enable `other` or interpret generic Persona class `id` as a specific document.
- Never persist Persona image bytes or URLs. Proxy a named artifact only after administrator authorization, an authoritative resource lookup, HTTPS/exact-host validation, redirect denial, content-type validation, and a bounded in-memory read.
- Write the test first for every task. Do not weaken middleware, authorization, or assertions to make a test pass.
- Commit only the files for the completed task. Keep unrelated working-tree changes untouched.

## Planned File Map

### Create

- `config/persona.php` — server-only API, Sandbox, template, ID-class, media-host, timeout, and webhook settings.
- `database/migrations/2026_10_26_000000_create_persona_identity_tables.php` — additive attempts, webhook delivery ledger, and guardian decision audits.
- `app/Models/PersonaInquiry.php` — immutable attempt metadata, policy helpers, and relationships.
- `app/Models/PersonaWebhookEvent.php` — deduplicated webhook delivery metadata.
- `app/Models/GuardianIdentityAudit.php` — append-only guardian status history.
- `app/Services/Persona/PersonaClient.php` — authenticated API calls and bounded media download.
- `app/Services/Persona/PersonaTemplateSelector.php` — server-side flow/template choice.
- `app/Services/Persona/PersonaPolicyEvaluator.php` — normalized deny-by-default outcome.
- `app/Services/Persona/PersonaInquiryService.php` — start, resume, replace, ownership, and local-submission gate.
- `app/Services/Persona/PersonaInquirySynchronizer.php` — authoritative, monotonic inquiry/verification synchronization.
- `app/Jobs/Identity/SyncPersonaInquiry.php` — queued webhook reconciliation.
- `app/Http/Requests/Auth/StartPersonaInquiryRequest.php` — validated local ID choice only.
- `app/Http/Controllers/Auth/PersonaIdentityController.php` — start, continue, return, refresh, and replacement actions.
- `app/Http/Controllers/Api/PersonaWebhookController.php` — minimal signed delivery ingestion.
- `app/Http/Controllers/Admin/PersonaEvidenceController.php` — authorized transient evidence proxy.
- `app/Http/Middleware/VerifyPersonaWebhook.php` — raw-body HMAC verification with rotation support.
- `app/Notifications/PersonaActionRequiredNotification.php` — one retry/expiration notification per state transition.
- `app/Notifications/PersonaReadyForLocalEvidenceNotification.php` — one authoritative-ready notification.
- `resources/views/auth/partials/persona-identity-step.blade.php` — shared user-facing state/actions.
- `resources/views/admin/parent-verifications/partials/persona-summary.blade.php` — summary, history, Sandbox label, and evidence actions.
- `tests/Feature/Identity/PersonaSchemaTest.php`
- `tests/Unit/Services/PersonaClientTest.php`
- `tests/Unit/Services/PersonaPolicyTest.php`
- `tests/Feature/Identity/PersonaInquiryFlowTest.php`
- `tests/Feature/Api/PersonaWebhookTest.php`
- `tests/Feature/Identity/PersonaLearnerIntegrationTest.php`
- `tests/Feature/Auth/PersonaGuardianIntegrationTest.php`
- `tests/Feature/Admin/PersonaEvidenceTest.php`
- `tests/Feature/Admin/PersonaReviewTest.php`
- `docs/persona-identity-verification-sandbox-runbook.md` — configuration, Sandbox scenarios, queue/webhook checks, and production blockers.
- `docs/superpowers/verification/2026-10-26-persona-identity-verification.md` — final automated and manual evidence.

### Modify

- `app/Models/User.php` — Persona attempts and guardian audit relationships.
- `app/Models/LearnerIdentityVerification.php` — Persona attempt relationship.
- `bootstrap/app.php` — register the webhook middleware alias and allow the existing queue configuration.
- `routes/api.php` — signed Persona webhook endpoint.
- `routes/auth.php` — authenticated Persona flow endpoints.
- `routes/admin.php` — administrator Persona evidence endpoint.
- `app/Http/Middleware/EnsureLearnerIdentityVerified.php` — allow the identity flow while continuing to block unrelated learner features.
- `app/Http/Controllers/Auth/LearnerIdentityVerificationController.php` — render/gate the Persona step and preserve grandfathered cases.
- `app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php` — require local selfie only for government-ID routes after Persona.
- `app/Services/Identity/LearnerIdentitySubmission.php` — enforce the current Persona attempt under the existing transaction.
- `app/Services/Identity/LearnerIdentityReview.php` — enforce Persona eligibility and school portrait-comparison attestation on direct approval.
- `app/Http/Controllers/Auth/GuardianIdentityVerificationController.php` — render/gate Persona before local guardian evidence.
- `app/Services/ParentChildVerificationService.php` — lock guardian decisions, enforce Persona eligibility, and append guardian audits.
- `app/Http/Controllers/Auth/ParentRegistrationController.php` — keep rejected guardian resubmission on the new Persona-first sequence.
- `app/Http/Controllers/Admin/LearnerIdentityVerificationController.php` — load Persona attempts and validate comparison attestation.
- `app/Http/Controllers/Admin/ParentChildVerificationController.php` — load guardian attempts and use service-level approval gates.
- `resources/views/auth/learner-identity-verification.blade.php` — place the Persona step before local learner evidence.
- `resources/views/auth/learner-identity-selfie.blade.php` — government-ID local selfie only.
- `resources/views/auth/learner-identity-status.blade.php` — accurate Persona/review/retry states.
- `resources/views/auth/guardian-verification.blade.php` — Persona-first guardian flow.
- `resources/views/auth/parent-verification-status.blade.php` — accurate guardian state and retry route.
- `resources/views/admin/parent-verifications/show-learner.blade.php` — include Persona summary and school comparison confirmation.
- `resources/views/admin/parent-verifications/show-parent.blade.php` — include Persona summary beside existing local evidence.
- `tests/Feature/Identity/LearnerIdentitySubmissionTest.php`
- `tests/Feature/Identity/LearnerIdentityAccessTest.php`
- `tests/Feature/Identity/LearnerIdentityReviewTest.php`
- `tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php`
- `tests/Feature/Auth/ParentChildVerificationResubmissionTest.php`
- `tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php`
- `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`

---

## Task 1: Add the append-only Persona and guardian-audit schema

**Files:** Create the migration and three models; modify `app/Models/User.php` and `app/Models/LearnerIdentityVerification.php`; test `tests/Feature/Identity/PersonaSchemaTest.php`.

**Interfaces:**

- `User::personaInquiries(): HasMany`
- `User::guardianIdentityAudits(): HasMany`
- `LearnerIdentityVerification::personaInquiries(): HasMany`
- `PersonaInquiry::user(): BelongsTo`
- `PersonaInquiry::learnerVerification(): BelongsTo`
- `GuardianIdentityAudit::guardian(): BelongsTo` and `actor(): BelongsTo`

- [ ] **Step 1: Confirm the isolated database before any test.** Run:

~~~powershell
Select-String -Path phpunit.xml -Pattern 'DB_DATABASE.*cc_db_test'
~~~

Expected: one match showing `cc_db_test`. Stop if it points at the development database.

- [ ] **Step 2: Write the failing schema/relationship test.** Assert all three tables and sensitive-field absence, create two attempts for one user, link a learner attempt to its case, reject duplicate inquiry/reference/idempotency identifiers, and verify guardian audits are append-only. Include this negative privacy assertion:

~~~php
$this->assertFalse(Schema::hasColumn('persona_inquiries', 'raw_payload'));
$this->assertFalse(Schema::hasColumn('persona_inquiries', 'document_number'));
$this->assertFalse(Schema::hasColumn('persona_inquiries', 'media_url'));
~~~

- [ ] **Step 3: Run the test and confirm red.** Run:

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaSchemaTest.php
~~~

Expected: FAIL because `persona_inquiries` and its model do not exist.

- [ ] **Step 4: Add the forward-only migration.** Create:

~~~php
Schema::create('persona_inquiries', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('learner_identity_verification_id')
        ->nullable()->constrained()->cascadeOnDelete();
    $table->string('flow_type', 32);
    $table->unsignedInteger('target_submission_round');
    $table->unsignedInteger('attempt_number');
    $table->string('local_document_type', 32);
    $table->string('local_government_id_type', 40)->nullable();
    $table->string('template_key', 24);
    $table->string('template_id', 80);
    $table->uuid('reference_id')->unique();
    $table->uuid('idempotency_key')->unique();
    $table->string('persona_inquiry_id', 80)->nullable()->unique();
    $table->string('government_id_verification_id', 80)->nullable();
    $table->string('selfie_verification_id', 80)->nullable();
    $table->string('persona_status', 40)->nullable();
    $table->string('verification_outcome', 24)->default('not_ready');
    $table->char('country_code', 2)->nullable();
    $table->string('id_class', 32)->nullable();
    $table->string('country_policy_status', 24)->default('not_applicable');
    $table->string('document_policy_status', 24)->default('not_applicable');
    $table->boolean('is_sandbox')->default(true);
    $table->timestamp('persona_event_at')->nullable();
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamp('expired_at')->nullable();
    $table->timestamp('superseded_at')->nullable();
    $table->string('failure_category', 48)->nullable();
    $table->timestamps();
    $table->unique(['user_id', 'attempt_number']);
    $table->index([
        'user_id', 'target_submission_round', 'superseded_at',
    ], 'persona_current_attempt_lookup');
});

Schema::create('persona_webhook_events', function (Blueprint $table): void {
    $table->id();
    $table->string('event_id', 80)->unique();
    $table->string('event_type', 80);
    $table->string('persona_inquiry_id', 80)->nullable()->index();
    $table->timestamp('event_created_at');
    $table->string('processing_status', 24)->default('received');
    $table->timestamp('received_at');
    $table->timestamp('processed_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->string('failure_category', 48)->nullable();
    $table->timestamps();
});

Schema::create('guardian_identity_audits', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('persona_inquiry_id')
        ->nullable()->constrained('persona_inquiries')->nullOnDelete();
    $table->string('action', 32);
    $table->string('from_status', 24)->nullable();
    $table->string('to_status', 24)->nullable();
    $table->unsignedInteger('attempt_number');
    $table->text('reason')->nullable();
    $table->timestamp('created_at');
    $table->index(['guardian_id', 'created_at']);
});
~~~

Order table creation exactly as shown so the guardian audit foreign key has a target. `down()` must throw `LogicException` with a message explaining that identity/audit history requires an explicit retention-approved migration; it must not drop these tables.

- [ ] **Step 5: Implement minimal model constants, fillable fields, datetime casts, and relationships.** Keep provider status as bounded text, not a PHP enum that claims Persona has a closed status set. Add helpers only for local invariants:

~~~php
public function isCurrent(): bool
{
    return $this->superseded_at === null;
}

public function isPolicyEligible(): bool
{
    if (! $this->isCurrent()
        || $this->verification_outcome !== self::OUTCOME_ELIGIBLE) {
        return false;
    }

    if ($this->flow_type === self::FLOW_TEEN_SCHOOL) {
        return $this->country_policy_status === self::POLICY_NOT_APPLICABLE
            && $this->document_policy_status === self::POLICY_NOT_APPLICABLE;
    }

    return $this->country_policy_status === self::POLICY_ELIGIBLE
        && $this->document_policy_status === self::POLICY_ELIGIBLE;
}
~~~

- [ ] **Step 6: Run the focused test and inspect SQL without applying it.** Run:

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaSchemaTest.php
php artisan migrate --pretend
~~~

Expected: test PASS; the new migration shows only three `CREATE TABLE` statements. Inspect any unrelated pending migration separately. Do not apply the migration to development until the implementation reaches its integration checkpoint.

- [ ] **Step 7: Commit Task 1.**

~~~text
feat(identity): add Persona attempt ledger
~~~

## Task 2: Add fail-closed configuration and the server-only Persona client

**Files:** Create `config/persona.php` and `app/Services/Persona/PersonaClient.php`; test `tests/Unit/Services/PersonaClientTest.php`.

**Interfaces:**

~~~php
public function createInquiry(
    string $templateId,
    string $idempotencyKey
): array;

public function resumeInquiry(
    string $inquiryId,
    string $idempotencyKey
): array;

public function retrieveInquiry(string $inquiryId): array;
public function retrieveGovernmentId(string $verificationId): array;
public function retrieveSelfie(string $verificationId): array;

/** @return array{content_type: string, bytes: string} */
public function downloadMedia(string $url): array;
~~~

- [ ] **Step 1: Write failing HTTP-contract tests.** Use `Http::fake()` and assert:

  - bearer authentication and `Persona-Version: 2026-09-29`;
  - create uses `POST https://api.withpersona.com/api/v1/inquiries`, only the configured template ID in `data.attributes.inquiry-template-id`, and the exact `Idempotency-Key`;
  - resume uses `POST /api/v1/inquiries/{id}/resume` and returns `meta.session-token` to the caller without persistence;
  - retrieve uses `GET /api/v1/inquiries/{id}` with a narrow `include=verifications`;
  - verification methods use the documented government-ID and selfie endpoints;
  - 401/403 are non-retryable configuration failures, while connection errors/429/5xx become sanitized retryable failures;
  - error text never includes the API key, response body, media URL, or session token;
  - media rejects HTTP, userinfo, non-allowlisted hosts, redirects, unexpected types, missing/oversized content length, and bodies above the byte limit.

- [ ] **Step 2: Run the client test and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/PersonaClientTest.php
~~~

Expected: FAIL because `PersonaClient` does not exist.

- [ ] **Step 3: Define and document safe configuration names.** The repository has no `.env.example`, so do not introduce a partial application environment file. `config/persona.php` consumes these names, and Task 11 records them in the runbook. The operator adds values to the local environment without committing or echoing them:

~~~dotenv
PERSONA_ENABLED=false
PERSONA_ENVIRONMENT=sandbox
PERSONA_ENFORCED_FROM=
PERSONA_API_KEY=
PERSONA_SELFIE_TEMPLATE_ID=
PERSONA_GOVID_SELFIE_TEMPLATE_ID=
PERSONA_WEBHOOK_SECRET=
~~~

Create `config/persona.php` with:

~~~php
return [
    'enabled' => (bool) env('PERSONA_ENABLED', false),
    'environment' => env('PERSONA_ENVIRONMENT', 'sandbox'),
    'enforced_from' => env('PERSONA_ENFORCED_FROM'),
    'api_key' => env('PERSONA_API_KEY'),
    'api_base_url' => 'https://api.withpersona.com/api/v1',
    'hosted_flow_url' => 'https://inquiry.withpersona.com/verify',
    'api_version' => '2026-09-29',
    'webhook_secret' => env('PERSONA_WEBHOOK_SECRET'),
    'webhook_tolerance_seconds' => 300,
    'templates' => [
        'selfie' => env('PERSONA_SELFIE_TEMPLATE_ID'),
        'govid_selfie' => env('PERSONA_GOVID_SELFIE_TEMPLATE_ID'),
    ],
    'government_id_classes' => [
        'passport' => 'pp',
        'drivers_license' => 'dl',
        'umid' => 'umid',
        'postal_id' => 'pid',
        'voters_id' => 'vid',
    ],
    'media_hosts' => ['files.withpersona.com'],
    'media_max_bytes' => 10 * 1024 * 1024,
    'connect_timeout_seconds' => 5,
    'timeout_seconds' => 15,
];
~~~

The integration service must reject disabled configuration, any environment other than `sandbox`, blank required keys/templates, a missing/invalid `PERSONA_ENFORCED_FROM` timestamp, and a blank webhook secret for webhook registration. Do not log configuration values.

- [ ] **Step 4: Implement the client with Laravel's installed HTTP client.** Use one private authenticated request builder, `acceptJson()`, `withToken()`, `Persona-Version`, `Key-Inflection: kebab`, fixed timeouts, and bounded retry only for connection failures, 429, and 5xx. POST retries are safe only with the supplied idempotency key. Parse only the fields each caller needs and throw sanitized domain-safe `RuntimeException` messages/categories.

Create and resume must return the session token to the immediate controller call but no model accepts a session-token field. Build the Hosted Flow URL with `http_build_query()`:

~~~php
return config('persona.hosted_flow_url').'?'.http_build_query([
    'inquiry-id' => $inquiryId,
    'session-token' => $sessionToken,
    'redirect-uri' => route('persona.identity.return'),
]);
~~~

- [ ] **Step 5: Implement bounded media reading.** Accept a URL only after `parse_url()` confirms `https`, no user/password, no fragment, and an exact lower-cased host in `media_hosts`. Disable redirects. Read a streamed body into memory up to `media_max_bytes + 1`, then fail closed. Permit `image/jpeg`, `image/png`, and `image/webp` only. Never expose the upstream URL in an exception.

- [ ] **Step 6: Run focused tests and formatting.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/PersonaClientTest.php
php vendor/bin/pint --test app/Services/Persona/PersonaClient.php config/persona.php
~~~

Expected: PASS and no formatting changes required.

- [ ] **Step 7: Commit Task 2.**

~~~text
feat(identity): add Persona API client
~~~

## Task 3: Implement server-side template selection and policy evaluation

**Files:** Create `app/Services/Persona/PersonaTemplateSelector.php`, `app/Services/Persona/PersonaPolicyEvaluator.php`, and `tests/Unit/Services/PersonaPolicyTest.php`.

**Interfaces:**

~~~php
/**
 * @return array{
 *   flow_type: string,
 *   template_key: string,
 *   template_id: string,
 *   local_document_type: string,
 *   local_government_id_type: ?string
 * }
 */
public function select(
    User $user,
    ?LearnerIdentityVerification $case,
    string $documentType,
    ?string $governmentIdType
): array;

/**
 * @return array{
 *   persona_status: string,
 *   verification_outcome: string,
 *   government_id_verification_id: ?string,
 *   selfie_verification_id: ?string,
 *   country_code: ?string,
 *   id_class: ?string,
 *   country_policy_status: string,
 *   document_policy_status: string,
 *   completed_at: ?CarbonImmutable,
 *   expired_at: ?CarbonImmutable
 * }
 */
public function evaluate(PersonaInquiry $attempt, array $inquiry): array;
~~~

- [ ] **Step 1: Write the policy matrix as failing tests.** Cover ages 12/13/17/18, school, institution, each mapped government ID, `other`, every unmapped current `guardian_identity` choice, guardian, dependent, instructor, and Connector roles. Assert a request field named `template_id` has no influence.

Add authoritative-response cases:

~~~php
#[DataProvider('ineligibleGovernmentResults')]
public function test_government_policy_fails_closed(
    ?string $country,
    ?string $idClass,
    string $governmentStatus,
    string $selfieStatus
): void {
    $result = $this->evaluator->evaluate(
        $this->governmentAttempt('passport'),
        $this->inquiryResponse(
            country: $country,
            idClass: $idClass,
            governmentStatus: $governmentStatus,
            selfieStatus: $selfieStatus,
        ),
    );

    $this->assertNotSame('eligible', $result['verification_outcome']);
}
~~~

The provider includes foreign country, null country, null class, generic `id`, class mismatch, missing government resource, missing selfie, failed resource, incomplete inquiry, and expired inquiry.

- [ ] **Step 2: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/PersonaPolicyTest.php
~~~

Expected: FAIL because selector/evaluator classes do not exist.

- [ ] **Step 3: Implement the selector from server-owned state.** Use `User::calculateAge` and `LearnerIdentityRequirement`; never accept age, role, template key, or template ID from request data. Return:

  - teen + school/institution → `teen_school` + `selfie`;
  - teen + mapped government ID → `teen_government` + `govid_selfie`;
  - adult learner + mapped government ID → `adult_government` + `govid_selfie`;
  - eligible guardian + mapped government ID → `guardian_government` + `govid_selfie`.

Throw a validation-safe exception for under-13 self-registration, dependents, unrelated roles, `other`, unmapped IDs, blank templates, or contradictory case/pathway state.

- [ ] **Step 4: Implement the evaluator as a pure parser.** Locate verification relationship IDs by exact Persona resource type, then consume the included resources or client-fetched resources keyed by type/id. Store only normalized status, IDs, country, class, policy results, and timestamps. For government flows, `completed + passed government ID + passed selfie + PH + exact mapped class` is eligible. For school/institution flows, `completed + passed selfie` is eligible while country/document policies remain `not_applicable`. Unknown fields/statuses yield `inconclusive`, never eligible.

- [ ] **Step 5: Run policy tests and formatting.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/PersonaPolicyTest.php
php vendor/bin/pint --test app/Services/Persona/PersonaTemplateSelector.php app/Services/Persona/PersonaPolicyEvaluator.php
~~~

Expected: PASS.

- [ ] **Step 6: Commit Task 3.**

~~~text
feat(identity): enforce Persona policy
~~~

## Task 4: Create, resume, return to, refresh, and replace owned inquiries

**Files:** Create `app/Services/Persona/PersonaInquiryService.php`, `app/Http/Requests/Auth/StartPersonaInquiryRequest.php`, `app/Http/Controllers/Auth/PersonaIdentityController.php`, `resources/views/auth/partials/persona-identity-step.blade.php`, and `tests/Feature/Identity/PersonaInquiryFlowTest.php`; modify `routes/auth.php` and `app/Http/Middleware/EnsureLearnerIdentityVerified.php`.

**Routes:**

~~~php
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::post('/identity/persona/start', [PersonaIdentityController::class, 'start'])
        ->name('persona.identity.start');
    Route::post('/identity/persona/continue', [PersonaIdentityController::class, 'resume'])
        ->name('persona.identity.resume');
    Route::get('/identity/persona/return', [PersonaIdentityController::class, 'return'])
        ->name('persona.identity.return');
    Route::post('/identity/persona/refresh', [PersonaIdentityController::class, 'refresh'])
        ->name('persona.identity.refresh');
    Route::post('/identity/persona/replace', [PersonaIdentityController::class, 'replace'])
        ->name('persona.identity.replace');
});
~~~

- [ ] **Step 1: Write failing flow tests.** Assert:

  - only a current eligible learner/guardian context can start;
  - the request accepts `document_type` and `government_id_type` only;
  - two sequential starts return the same attempt and make one Persona create call;
  - a simulated concurrent start is serialized by the user/case lock and unique attempt number;
  - the Hosted redirect contains inquiry ID, the immediate session token, and the application return route, but no API key/template ID;
  - ownership is checked for continue, return, refresh, and replacement;
  - return query `status` and `fields` are ignored;
  - only failed/expired current attempts can be explicitly replaced;
  - replacement preserves and supersedes the prior row;
  - an existing pending/approved application case is not sent through Persona;
  - the learner access middleware permits these named routes but keeps the learning area blocked.

- [ ] **Step 2: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaInquiryFlowTest.php
~~~

Expected: FAIL because routes/service/controller do not exist.

- [ ] **Step 3: Implement transactional attempt creation.** In one database transaction:

  1. lock the authenticated `users` row and current learner case when present;
  2. return the existing current attempt for the target round when it is resumable;
  3. derive `attempt_number = max + 1` and create the local row with UUID reference/idempotency values;
  4. call Persona outside any long-held transaction only after the durable local row exists;
  5. associate the returned inquiry ID/status in a short locked transaction;
  6. on ambiguous timeout, retry with the same idempotency key rather than inserting another row.

Do not hold a database lock across normal user time in Hosted Flow. If an API create definitively fails, retain the attempt with a sanitized failure category so a safe retry uses its idempotency key.

- [ ] **Step 4: Implement resume and Hosted Flow redirect.** `POST /inquiries/{id}/resume` receives its own deterministic per-action idempotency key, returns `meta.session-token`, and redirects immediately. Never store or log the token. Use same-tab redirect behavior; do not add client JavaScript.

- [ ] **Step 5: Implement safe return and refresh.** Match Persona's returned `inquiry-id` to the authenticated user's current local attempt. Ignore returned `status`, `fields`, and `reference-id` as evidence. Invoke the shared synchronizer from Task 5 once available; until then the test may bind a mocked synchronizer contract. Redirect to the existing learner or guardian identity page with a generic state message.

- [ ] **Step 6: Implement explicit replacement.** Permit replacement only after an authoritative failed/expired state. Under a lock, set `superseded_at` on the current attempt and insert the next attempt for the same target submission round. A stale attempt can never be unsuperseded.

- [ ] **Step 7: Render the shared partial.** Show one of Begin, Continue, Refresh, or Start a new attempt based on local state. Include concise text that Persona is one evidence step, administrator approval is still required, and every outcome is simulated Sandbox data. Use POST forms with CSRF for state changes and a normal GET return route only for Persona's redirect.

- [ ] **Step 8: Run focused tests and inspect routes.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaInquiryFlowTest.php
php artisan route:list --name=persona.identity
~~~

Expected: PASS; five authenticated flow routes, with no route accepting a user or Persona attempt ID.

- [ ] **Step 9: Commit Task 4.**

~~~text
feat(identity): add Persona hosted flow
~~~

## Task 5: Verify webhooks and synchronize authoritative state through the queue

**Files:** Create `app/Http/Middleware/VerifyPersonaWebhook.php`, `app/Http/Controllers/Api/PersonaWebhookController.php`, `app/Services/Persona/PersonaInquirySynchronizer.php`, `app/Jobs/Identity/SyncPersonaInquiry.php`, and `tests/Feature/Api/PersonaWebhookTest.php`; modify `bootstrap/app.php` and `routes/api.php`.

**Endpoint:** `POST /api/webhooks/persona`, named `api.webhooks.persona`, with `persona.webhook` and a focused throttle.

- [ ] **Step 1: Write failing signature and delivery tests.** Generate a synthetic header from the exact raw JSON:

~~~php
$timestamp = now()->timestamp;
$signature = hash_hmac(
    'sha256',
    $timestamp.'.'.$rawBody,
    'test-persona-webhook-secret',
);
$header = "t={$timestamp},v1={$signature}";
~~~

Cover valid signature, altered body, absent header, wrong secret, stale/future timestamp, multiple space-separated signature sets during rotation, malformed sets, duplicate event ID, unknown inquiry, cross-user identifier, and prompt response with a queued job.

- [ ] **Step 2: Write failing synchronizer tests in the same file.** Assert that it:

  - refetches the inquiry rather than trusting event outcome fields;
  - retrieves the exact referenced government-ID/selfie resources;
  - applies the policy evaluator;
  - records exact provider status plus normalized fields only;
  - never regresses a current row on an older event;
  - may update its historical row but never make a superseded row current;
  - sends no duplicate transition notification;
  - marks event processing failed with a sanitized category for a retryable API error.

- [ ] **Step 3: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Api/PersonaWebhookTest.php
~~~

Expected: FAIL because the middleware, endpoint, and synchronizer do not exist.

- [ ] **Step 4: Implement raw-body signature verification.** Read `$request->getContent()` before JSON parsing. Split the header on spaces, then each set on commas, accept a set only when it has one integer `t` and hexadecimal `v1`, enforce the configured tolerance, compute `hash_hmac('sha256', $t.'.'.$raw, $secret)`, and compare with `hash_equals`. Accept when any active-signature set matches. Return 401 with a generic body otherwise.

- [ ] **Step 5: Register and ingest the webhook.** Add the middleware alias in `bootstrap/app.php`. Validate only:

~~~text
data.id
data.type
data.attributes.name
data.attributes.created-at
data.attributes.payload.data.id
~~~

Map those fields to the event ID, event type, created time, and inquiry ID according to the versioned event envelope actually captured from the Sandbox webhook test. Insert with `firstOrCreate` on event ID; do not store the raw body. A duplicate returns 200 without another job. An unknown inquiry is marked `ignored` and returns 200. Dispatch `SyncPersonaInquiry` after commit for a known inquiry.

- [ ] **Step 6: Implement the queued job.** Use a short retry policy such as `$tries = 5` and backoff `[30, 120, 600, 1800]`. Lock the event, no-op if processed, load the attempt by stored Persona inquiry ID, and call the synchronizer. Mark processed only after a successful authoritative refresh. Retry transient provider/queue failures; permanently fail malformed local association without leaking data.

- [ ] **Step 7: Implement authoritative synchronization.** Retrieve inquiry with `include=verifications`, fetch each required typed resource by ID, evaluate, then update the matching attempt inside a transaction. Use Persona's resource/event timestamp only to reject older snapshots; never infer success from arrival order. Fire domain transition notifications after commit in Task 10.

- [ ] **Step 8: Reuse the synchronizer in return and refresh.** Remove the temporary mocked seam from Task 4. Both paths load the authenticated user's local attempt first, then synchronize. A mismatch returns 404/authorization failure and never queries an arbitrary Persona ID.

- [ ] **Step 9: Run the focused tests and queue serialization check.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Api/PersonaWebhookTest.php tests/Feature/Identity/PersonaInquiryFlowTest.php
php artisan route:list --name=api.webhooks.persona
~~~

Expected: PASS; one API route with signature middleware; queued job serializes without secrets or response payloads.

- [ ] **Step 10: Commit Task 5.**

~~~text
feat(identity): sync signed Persona events
~~~

## Task 6: Put Persona before learner local evidence without breaking existing cases

**Files:** Create `tests/Feature/Identity/PersonaLearnerIntegrationTest.php`; modify `app/Http/Controllers/Auth/LearnerIdentityVerificationController.php`, `app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php`, `app/Services/Identity/LearnerIdentitySubmission.php`, `resources/views/auth/learner-identity-verification.blade.php`, `resources/views/auth/learner-identity-selfie.blade.php`, `resources/views/auth/learner-identity-status.blade.php`, `tests/Feature/Identity/LearnerIdentitySubmissionTest.php`, and `tests/Feature/Identity/LearnerIdentityAccessTest.php`.

**Required evidence by flow:**

| Flow | Persona evidence | Local evidence |
|---|---|---|
| Teen school/institution | Selfie | Existing school/institution ID sides; no new local selfie |
| Teen government | Government ID + selfie | Existing government ID sides and local selfie |
| Adult government | Government ID + selfie | Existing government ID sides and local selfie |

- [ ] **Step 1: Write failing integration tests.** Cover:

  - a new unsubmitted teen/adult case sees the Persona step before any local file input;
  - a rejected case requires an eligible Persona attempt for `submission_round + 1`;
  - an existing pending or approved case remains on its current status/review path without a retroactive Persona requirement;
  - a completed but foreign/mismatched/superseded/wrong-round attempt cannot open or post local evidence;
  - a teen school/institution route may finish local submission without a local selfie;
  - a teen/adult government route still requires the existing local selfie;
  - a posted local document type must exactly equal the type frozen on the current Persona attempt;
  - successful local submission moves only the application case to pending and leaves Persona history intact;
  - direct requests from another user and blocked learner routes remain denied.

Use a helper that creates normalized local attempt rows; do not fake authorization by disabling middleware.

- [ ] **Step 2: Run the learner tests and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaLearnerIntegrationTest.php tests/Feature/Identity/LearnerIdentitySubmissionTest.php tests/Feature/Identity/LearnerIdentityAccessTest.php
~~~

Expected: new integration assertions FAIL while existing tests continue to expose the current local-first behavior.

- [ ] **Step 3: Add one authoritative local-submission guard.** Implement on `PersonaInquiryService`:

~~~php
public function assertLearnerMaySubmit(
    User $learner,
    LearnerIdentityVerification $case,
    string $documentType,
    ?string $governmentIdType,
): PersonaInquiry;
~~~

Inside the existing submission transaction, lock the case and current attempt, then require:

- authenticated learner owns the case;
- case is unsubmitted or rejected, never pending/approved;
- attempt is current, eligible, belongs to the case and learner;
- `target_submission_round === $case->submission_round + 1`;
- frozen local document/government type exactly matches the validated form;
- flow still matches the learner's current DOB-derived pathway.

Call this guard from `LearnerIdentitySubmission`, not only the controller, so direct POSTs cannot bypass it.

- [ ] **Step 4: Render Persona first.** In `create()`:

  - keep current pending → status and approved → profile behavior;
  - for an unsubmitted/rejected case, load the current target-round attempt;
  - before eligibility, render only the local ID choice plus the shared Persona partial;
  - after eligibility, render the existing private local upload fields with the frozen choice displayed as non-editable text plus hidden server-validated values.

Do not stage local files before the eligible Persona attempt. Existing draft cleanup continues to handle abandoned local forms after this point.

- [ ] **Step 5: Make the second learner page flow-aware.** Preserve the existing two-page staging pattern. For school/institution:

  - stage the ID on page one;
  - page two shows the simulated Persona selfie explanation and final consent;
  - no local selfie input is rendered or accepted.

For government routes, page two keeps the current local selfie capture/upload. Update `SubmitLearnerIdentityRequest` and `LearnerIdentitySubmission` so the selfie is required only for government flows. Do not delete an existing selfie from an older submission until the new transaction commits.

- [ ] **Step 6: Keep status language exact.** Distinguish:

  - Persona action required;
  - Persona processing;
  - Persona evidence ready, local evidence required;
  - local evidence pending administrator review;
  - application rejected with the existing reason;
  - application approved.

Every Persona state includes `Simulated Sandbox result`. Only the final application state uses approved language.

- [ ] **Step 7: Run learner integration and existing regressions.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaLearnerIntegrationTest.php tests/Feature/Identity/LearnerIdentitySubmissionTest.php tests/Feature/Identity/LearnerIdentityAccessTest.php tests/Feature/Identity/LearnerIdentityRequirementTest.php
~~~

Expected: PASS, including old pending/approved cases and school submissions without a duplicated local selfie.

- [ ] **Step 8: Commit Task 6.**

~~~text
feat(identity): gate learner evidence by Persona
~~~

## Task 7: Put Persona before guardian evidence and add durable guardian audits

**Files:** Create `tests/Feature/Auth/PersonaGuardianIntegrationTest.php`; modify `app/Http/Controllers/Auth/GuardianIdentityVerificationController.php`, `app/Services/ParentChildVerificationService.php`, `app/Http/Controllers/Auth/ParentRegistrationController.php`, `resources/views/auth/guardian-verification.blade.php`, `resources/views/auth/parent-verification-status.blade.php`, `tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php`, and `tests/Feature/Auth/ParentChildVerificationResubmissionTest.php`.

- [ ] **Step 1: Write failing guardian tests.** Assert:

  - a new/unsubmitted guardian must complete an eligible `guardian_government` attempt before seeing or posting the local ID form;
  - an existing pending or approved guardian is grandfathered;
  - rejection starts a new Persona target round and retains the previous attempt/audit;
  - the local guardian form keeps only its existing front/back rules and does not require a new local selfie;
  - foreign, mismatched, stale, superseded, or another user's attempt is rejected server-side;
  - dependent creation remains blocked until the existing guardian approval/onboarding conditions pass;
  - guardian Persona success alone neither approves the guardian nor proves a dependent relationship;
  - submit, approve, reject, and resubmit append audit rows without overwriting earlier rows.

- [ ] **Step 2: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php
~~~

Expected: new Persona/audit assertions FAIL.

- [ ] **Step 3: Add the guardian submission guard.** Implement:

~~~php
public function assertGuardianMaySubmit(
    User $guardian,
    string $governmentIdType,
): PersonaInquiry;
~~~

Lock the guardian row, derive the next attempt/decision round from the audit history and current status, and require a current eligible `guardian_government` attempt with the same frozen government-ID type. Call it inside `ParentChildVerificationService::submitParentIdentity()` before moving any local files or changing status.

- [ ] **Step 4: Make guardian decisions transactional and audited.** Existing guardian fields remain the current-state source of truth. Under a row lock:

  - submission appends `submitted` with previous/next status and attempt;
  - approval appends `approved` with explicit actor;
  - rejection appends `rejected` with explicit actor and reason;
  - resubmission never edits an old audit row.

Dispatch existing notifications after commit. Preserve old local evidence until a replacement submission commits, then use the current safe cleanup behavior.

- [ ] **Step 5: Render the shared Persona step before the guardian upload.** Freeze the supported government-ID choice after attempt creation. Keep the existing guardian approval/profile-completion explanation, and state that dependent creation and relationship review remain separate. Show no local selfie input.

- [ ] **Step 6: Keep rejected resubmission on this sequence.** `ParentRegistrationController::resubmitParentVerification()` continues to route only rejected guardians, but the destination first requires a new target-round Persona attempt. Pending/approved guardians cannot create replacement attempts or overwrite evidence.

- [ ] **Step 7: Run guardian/dependent regressions.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php
~~~

Expected: PASS; dependent behavior is unchanged until the existing guardian approval boundary is met.

- [ ] **Step 8: Commit Task 7.**

~~~text
feat(identity): gate guardian evidence by Persona
~~~

## Task 8: Proxy Persona images only to authorized administrators

**Files:** Create `app/Http/Controllers/Admin/PersonaEvidenceController.php` and `tests/Feature/Admin/PersonaEvidenceTest.php`; modify `routes/admin.php` and `app/Services/Persona/PersonaClient.php`.

**Route:**

~~~php
Route::get(
    '/parent-verifications/persona/{personaInquiry}/{artifact}',
    [PersonaEvidenceController::class, 'show'],
)->whereIn('artifact', [
    'government-id-front',
    'government-id-back',
    'selfie',
])->name('parent-verifications.persona.evidence');
~~~

- [ ] **Step 1: Write failing authorization and SSRF tests.** Cover unauthenticated, learner, guardian, instructor, and non-admin denial; authorized admin success; mismatched artifact/flow; arbitrary artifact; missing verification ID; upstream 404; HTTP URL; userinfo; deceptive subdomain; IP host; redirect; bad MIME; oversized content; chunked body over limit; and generic no-leak error output.

Assert a success response contains:

~~~php
$response->assertHeader('Cache-Control', 'private, no-store');
$response->assertHeader('X-Content-Type-Options', 'nosniff');
$this->assertStringNotContainsString('files.withpersona.com', $response->headers->get('Location', ''));
Storage::disk('local')->assertMissing('persona');
~~~

- [ ] **Step 2: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/PersonaEvidenceTest.php
~~~

Expected: FAIL because the admin route/controller do not exist.

- [ ] **Step 3: Resolve semantic artifacts server-side.** The controller accepts only the local attempt and constrained artifact name. It authorizes with the existing administrator boundary, then:

  - `selfie` → retrieves the stored selfie verification and selects documented `center-photo-url`;
  - `government-id-front`/`back` → retrieves the stored government verification and selects the exact `photo-urls` entry whose `page` is `front`/`back`;
  - a school/institution attempt permits only `selfie`;
  - a government attempt permits only artifacts actually returned.

Never accept a verification ID or URL from query/body input.

- [ ] **Step 4: Download and return the bounded bytes.** Delegate URL checks and byte limits to `PersonaClient::downloadMedia()`. Return an inline response with the allowlisted MIME and private/no-store/nosniff headers. Use a generic 404 when the attempt/artifact does not belong to a reviewable subject and a generic 503 when Persona media is temporarily unavailable.

- [ ] **Step 5: Run focused tests and route inspection.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/PersonaEvidenceTest.php tests/Unit/Services/PersonaClientTest.php
php artisan route:list --name=parent-verifications.persona.evidence
~~~

Expected: PASS; exactly one administrator evidence route; no public or user-facing Persona media route.

- [ ] **Step 6: Commit Task 8.**

~~~text
feat(identity): proxy private Persona evidence
~~~

## Task 9: Extend existing admin reviews and enforce approval gates in services

**Files:** Create `resources/views/admin/parent-verifications/partials/persona-summary.blade.php` and `tests/Feature/Admin/PersonaReviewTest.php`; modify `app/Http/Controllers/Admin/LearnerIdentityVerificationController.php`, `app/Http/Controllers/Admin/ParentChildVerificationController.php`, `app/Services/Identity/LearnerIdentityReview.php`, `app/Services/ParentChildVerificationService.php`, `resources/views/admin/parent-verifications/show-learner.blade.php`, `resources/views/admin/parent-verifications/show-parent.blade.php`, `tests/Feature/Identity/LearnerIdentityReviewTest.php`, `tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php`, and `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`.

- [ ] **Step 1: Inspect every approval caller before changing service signatures.** Use CodeGraph:

~~~text
codegraph_context: LearnerIdentityReview
codegraph_callers: LearnerIdentityReview approval method
codegraph_context: ParentChildVerificationService
codegraph_callers: guardian approval and rejection methods
~~~

Update every surfaced production/test caller in the same task. Do not add a parallel approval path.

- [ ] **Step 2: Write failing direct-request gate tests.** For both learner and guardian approvals, assert denial when the current attempt is absent, wrong user/case, wrong round, superseded, failed, incomplete, foreign, mismatched, or not Sandbox-labelled. Assert existing pending cases created before Persona enablement remain reviewable under the grandfather rule.

For teen school/institution approval, assert `persona_portrait_compared` is required and must be accepted:

~~~php
$this->actingAs($admin)
    ->post(route('admin.parent-verifications.learners.approve', $case), [])
    ->assertSessionHasErrors('persona_portrait_compared');
~~~

- [ ] **Step 3: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/PersonaReviewTest.php tests/Feature/Identity/LearnerIdentityReviewTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php
~~~

Expected: new gate/summary assertions FAIL.

- [ ] **Step 4: Enforce approval inside the locked service boundary.** Add a focused `assertApprovalEligible()` method to `PersonaInquiryService` and call it from `LearnerIdentityReview` and `ParentChildVerificationService` after locking the application record. The method enforces current user/case/round/flow/policy state. The controller still validates input for useful errors, but the service is authoritative.

Grandfather only an application submission that was already pending when Persona was enabled and has no Persona attempt for that round. A new post-enable pending submission without an attempt is invalid, not grandfathered. Record the enablement cutover timestamp in `config/persona.php` as an explicit non-secret configuration value used by this rule.

- [ ] **Step 5: Load attempts into existing detail controllers.** Eager-load the current attempt and descending history for the reviewed subject. Do not query Persona while rendering a page; summaries come from synchronized local metadata and image requests use the proxy.

- [ ] **Step 6: Render one shared compact summary.** Show:

  - `Simulated Sandbox result` prominently;
  - masked inquiry reference, purpose, status/outcome, country/class policy, last sync;
  - current/superseded attempt history;
  - only the evidence buttons valid for the flow;
  - school/institution warning that Persona did not authenticate the ID or perform an automated ID-to-selfie match;
  - government warning that classification is not proof of authenticity or ownership.

Keep local evidence in its existing section and label it `Submitted to Conscious Connections`; label proxied evidence `Viewed from Persona`. Do not render scores, document numbers, extracted identity fields, raw check details, raw JSON, or remote URLs.

- [ ] **Step 7: Add explicit school comparison attestation.** The learner approval form requires an unchecked checkbox stating that the administrator viewed the local school/institution ID and the Persona selfie and manually compared the portrait. Pass the boolean to the service; reject false for `teen_school`. Do not require it for government routes.

- [ ] **Step 8: Run all admin review tests.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/PersonaReviewTest.php tests/Feature/Admin/PersonaEvidenceTest.php tests/Feature/Identity/LearnerIdentityReviewTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
~~~

Expected: PASS, including direct POST denial and existing review regressions.

- [ ] **Step 9: Commit Task 9.**

~~~text
feat(identity): show Persona in admin review
~~~

## Task 10: Add transition-only notifications and complete user state recovery

**Files:** Create `app/Notifications/PersonaActionRequiredNotification.php` and `app/Notifications/PersonaReadyForLocalEvidenceNotification.php`; modify `app/Services/Persona/PersonaInquirySynchronizer.php`, `resources/views/auth/partials/persona-identity-step.blade.php`, `resources/views/auth/learner-identity-status.blade.php`, `resources/views/auth/parent-verification-status.blade.php`, `tests/Feature/Identity/PersonaInquiryFlowTest.php`, and `tests/Feature/Api/PersonaWebhookTest.php`.

- [ ] **Step 1: Write failing transition tests.** With `Notification::fake()`, assert:

  - the first transition into eligible sends one ready notification;
  - duplicate webhook, return, and refresh calls do not resend it;
  - the first transition into retryable failed/expired sends one action-required notification;
  - repeated synchronization in the same state sends nothing;
  - a superseded attempt sends nothing;
  - notifications contain no inquiry ID, template ID, country/class, verification ID, signed URL, or raw provider reason;
  - notification failure after commit does not roll back synchronized state.

- [ ] **Step 2: Run and confirm red.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Api/PersonaWebhookTest.php tests/Feature/Identity/PersonaInquiryFlowTest.php
~~~

Expected: notification assertions FAIL.

- [ ] **Step 3: Dispatch only on a normalized state transition.** Capture the pre-update outcome while the attempt is locked. After commit:

  - non-eligible → eligible: send `PersonaReadyForLocalEvidenceNotification`;
  - non-retryable → failed/expired retryable: send `PersonaActionRequiredNotification`;
  - all other changes: no notification.

The notification links to the existing authenticated learner or guardian identity/status page, not Persona directly.

- [ ] **Step 4: Complete recovery actions in the shared partial.** State-to-action mapping:

| Current state | Action |
|---|---|
| No attempt | Begin verification |
| Created/pending | Continue verification |
| Provider processing/inconclusive | Refresh status |
| Failed but resumable | Continue verification |
| Failed/expired and not resumable | Start a new attempt |
| Eligible | Continue to local evidence |
| Superseded | No action; show in history only |

Use generic provider-unavailable text and preserve existing form input/errors. Never display a provider exception or webhook detail.

- [ ] **Step 5: Run focused notification/state tests.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Api/PersonaWebhookTest.php tests/Feature/Identity/PersonaInquiryFlowTest.php tests/Feature/Identity/PersonaLearnerIntegrationTest.php tests/Feature/Auth/PersonaGuardianIntegrationTest.php
~~~

Expected: PASS with one notification per meaningful transition.

- [ ] **Step 6: Commit Task 10.**

~~~text
feat(identity): notify Persona transitions
~~~

## Task 11: Document and execute the Sandbox operational checks

**Files:** Create `docs/persona-identity-verification-sandbox-runbook.md`; modify `config/persona.php` only if the verified Dashboard contract requires a non-secret event allowlist; test all Persona-focused suites.

- [ ] **Step 1: Write the runbook before connecting a webhook.** Include:

  - required variable names, with values redacted;
  - exact Sandbox-only guard and enablement/cutover procedure;
  - `POST /api/webhooks/persona` endpoint and `Persona-Version: 2026-09-29`;
  - Persona Dashboard redirect-domain allowlist requirement;
  - queue worker command `php artisan queue:work --queue=default --tries=5`;
  - local tunnel requirement for Dashboard-to-local delivery, without prescribing a vendor;
  - how to rotate the webhook secret and test multiple signature sets;
  - how to inspect failed jobs without printing payloads;
  - retry/replay, duplicate delivery, out-of-order delivery, expired/resume, replacement, and media-unavailable procedures;
  - every user/admin Sandbox scenario in the approved design;
  - enabled government-ID mapping evidence table;
  - rollback behavior: set `PERSONA_ENABLED=false`, stop new starts, retain all rows/local evidence, and keep existing pending reviews operable;
  - production privacy/legal/minors/retention/security/operations gates.

- [ ] **Step 2: Verify the actual Dashboard event contract.** In the Persona Sandbox Dashboard, inspect the available inquiry events for API version `2026-09-29`. Subscribe only to inquiry events that can affect progress/completion/failure/expiration. Record their exact names in the runbook and, if used by the controller, an explicit config allowlist. Do not invent an event name from memory.

- [ ] **Step 3: Verify redirect and webhook configuration.** Add only the current application domain to Persona's allowed redirect domains, set the safe return route, create the Sandbox webhook, and place its secret in local `.env` manually. Do not commit or echo the value.

- [ ] **Step 4: Prove every enabled ID mapping.** For passport/`pp`, driver's license/`dl`, UMID/`umid`, Postal ID/`pid`, and Voter's ID/`vid`:

  - confirm the configured template offers the document;
  - run the documented Sandbox simulation;
  - confirm the authoritative verification returns `PH` and the exact class;
  - record pass/fail and date in the runbook;
  - remove any mapping from `config/persona.php` that cannot be proven.

Do not enable national ID, PhilHealth, senior-citizen ID, PWD ID, PRC ID, `other`, or generic `id` without a later exact-contract change and tests.

- [ ] **Step 5: Exercise the manual Sandbox matrix.** Complete teen school, teen institution, teen government, adult government, and guardian flows in the browser. For each, verify same-tab return, resume in a new tab, refresh, failure, expiration/replacement, local evidence, admin summary, authorized preview, approve/reject, rejection reason, and resubmission. Verify dependent registration remains separate.

- [ ] **Step 6: Inspect logs and storage.** Search application logs and private/public storage for known synthetic inquiry/session/media markers. Expected: operational attempt IDs may appear; API key, webhook secret, session token, raw payload, signed URL, and Persona bytes do not. Confirm no file was created under a Persona-specific storage path.

- [ ] **Step 7: Run all Persona-focused tests after the live contract check.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/PersonaClientTest.php tests/Unit/Services/PersonaPolicyTest.php tests/Feature/Identity/PersonaSchemaTest.php tests/Feature/Identity/PersonaInquiryFlowTest.php tests/Feature/Api/PersonaWebhookTest.php tests/Feature/Identity/PersonaLearnerIntegrationTest.php tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/Admin/PersonaEvidenceTest.php tests/Feature/Admin/PersonaReviewTest.php
~~~

Expected: PASS. Any actual Sandbox response shape that differs from a fake requires updating both the parser and a sanitized synthetic fixture/test before proceeding.

- [ ] **Step 8: Commit Task 11.**

~~~text
docs(identity): add Persona sandbox runbook
~~~

## Task 12: Apply the additive migration and run final verification

**Files:** Create `docs/superpowers/verification/2026-10-26-persona-identity-verification.md`; modify only files required by failures demonstrated in this task.

- [ ] **Step 1: Recheck the working tree and pending SQL.**

~~~powershell
git status --short
php artisan migrate:status
php artisan migrate --pretend
~~~

Expected: only intended task files are changed; the Persona migration contains three additive table creations and no data update/delete/drop. Review unrelated pending migrations before applying anything.

- [ ] **Step 2: Apply the migration incrementally to the inspected development database.**

~~~powershell
php artisan migrate
~~~

Expected: the single Persona identity migration reports `DONE`; existing tables/data remain. Never substitute a reset command.

- [ ] **Step 3: Run focused existing regressions serially.**

~~~powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
~~~

Expected: all PASS.

- [ ] **Step 4: Run the complete automated checks.**

~~~powershell
php vendor/bin/pint --test
php vendor/bin/phpunit --do-not-cache-result
npm.cmd run build
~~~

Expected: Pint clean, full PHPUnit PASS, Vite production build succeeds. If an existing unrelated baseline failure appears, capture its exact command/output separately and do not describe the full suite as passing.

- [ ] **Step 5: Run route/config safety checks without exposing secrets.**

~~~powershell
php artisan route:list --name=persona
php artisan route:list --path=identity
git grep -n -E 'PERSONA_(API_KEY|WEBHOOK_SECRET)=' -- ':!*.example'
git grep -n -E 'session-token|front-photo-url|center-photo-url' -- app database resources
~~~

Expected:

- user flow, webhook, and admin evidence routes are present with intended middleware;
- no committed secret assignment;
- session tokens/media URLs appear only in transient client/parser logic and are never model fillable fields, migrations, logs, views, or notifications.

- [ ] **Step 6: Complete accessibility and responsive browser checks.** At phone, tablet, and desktop widths, verify keyboard/focus order, form labels, 44px touch targets, error announcements, status text independent of color, same-tab return, back-button behavior, and admin image alternatives. Test with a screen reader. Record browser/device and result; do not convert an unrun manual check into a pass.

- [ ] **Step 7: Perform a final privacy/authorization review.** Confirm:

  - no public Persona route or storage artifact;
  - no arbitrary URL/verification ID input;
  - direct local submission and direct admin approval fail when policy is not eligible;
  - old event/current-attempt races fail closed;
  - existing pending/approved cases remain operable;
  - guardian success does not unlock dependent creation;
  - all Persona UI says simulated Sandbox;
  - non-Sandbox configuration fails closed.

- [ ] **Step 8: Write the verification record.** In `docs/superpowers/verification/2026-10-26-persona-identity-verification.md`, separate:

  1. implementation completed;
  2. automated checks with command, timestamp, and counts;
  3. manual Sandbox/browser/accessibility checks;
  4. unrun checks or observed baseline failures;
  5. production release blockers.

Do not include secrets, inquiry IDs, signed URLs, raw identity data, or screenshots containing identity evidence.

- [ ] **Step 9: Review the final diff and commit.**

~~~powershell
git diff --check
git status --short
git diff --stat
~~~

Expected: no whitespace errors and no unrelated files. Commit:

~~~text
feat(identity): integrate Persona sandbox
~~~

## Definition of Done

- The two configured Persona templates are selected only by server-owned policy.
- One current attempt exists per user/target round; repeat starts are idempotent and replacement history is retained.
- Signed webhook delivery is deduplicated and queued; all decisions use authoritative API retrieval.
- Foreign, missing, ambiguous, unsupported, mismatched, stale, and superseded results fail closed.
- Learner and guardian local evidence cannot be posted without the current eligible attempt, except explicitly grandfathered already-pending cases.
- Teen school/institution review uses the local ID plus transient Persona selfie and requires administrator comparison attestation.
- Government learner routes retain local ID plus local selfie; guardian routes retain local ID only.
- Application approval remains an authorized administrator action enforced inside locked services.
- Persona media and sensitive payloads are not persisted, logged, or exposed.
- Existing pending/approved identities, rejection reasons, resubmission, access gates, guardian onboarding, and dependent relationships pass regression tests.
- Sandbox labelling is visible everywhere Persona data appears.
- Automated evidence and manual/unrun gates are reported separately.
- Production remains disabled pending the approved privacy, legal, minors, retention, security, and operational reviews.
