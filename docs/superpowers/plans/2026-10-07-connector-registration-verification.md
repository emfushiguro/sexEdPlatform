# Connector Registration and Type-Specific Organization Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current evidence-free Connector approval path with a Cavite-only, type-specific organization verification workflow that keeps all existing Connector capabilities and operational access rules intact.

**Architecture:** Preserve `connectors.status` as the operational access boundary and add a separate evidence lifecycle driven by one version-controlled PHP requirement registry. Snapshot the resolved requirements per application, store evidence privately with immutable replacement history, route all mutations through one row-locked `ConnectorVerificationService`, and require document-level decisions plus a server-enforced checklist before the existing approval transition can occur.

**Tech Stack:** Laravel 12, PHP 8.2+, Eloquent/MySQL, Blade, Alpine.js, Tailwind CSS, Laravel private/public disks, database and mail notifications, PHPUnit 11, Pint, and Vite. No new dependency.

## Global Constraints

- Approved design: `docs/superpowers/specs/2026-10-07-connector-registration-verification-design.md`.
- UI source of truth: `design.md`; use Poppins, the existing gray/white surfaces and brand-purple accents, the Payment Management admin baseline, visible status text, and accessible controls.
- Keep `category` as the service/community domain and add `organization_type` as the legal or institutional type. Do not rename or repurpose the existing category field.
- Keep `ConnectorAccessService` and every workspace, membership, seminar, community, subscription, and marketplace check on operational `connectors.status = verified`.
- Treat `verification_status` as evidence state only. Document expiry sets re-verification state; it does not automatically suspend operational access.
- Preserve existing verified Connectors as `legacy_verified`. Do not require them to re-upload evidence during migration.
- Existing pending applications remain intact, but approval must fail until the applicant selects an organization type and submits the current evidence requirements.
- Evidence lives on the private `local` disk and is never linked publicly. The optional logo follows the existing public profile-media convention and is not verification evidence.
- Accept evidence only as PDF, JPEG, PNG, or WebP up to 10 MB per file. Accept a logo only as JPEG, PNG, or WebP up to 2 MB. Validate detected MIME type as well as extension.
- Reuse a current approved `LearnerIdentityVerification` for representative identity when compatible; save only its reference. Never copy its private files. Representative authority evidence is always separate.
- All review writes must validate `current_submission_round`, lock the Connector and affected rows, write an audit record, and notify only after commit.
- Applicant-facing reasons and internal notes are separate fields. Never expose internal notes in applicant views, notifications, logs intended for applicants, or public output.
- Preserve accepted documents during partial resubmission. Replacements create new rows and mark only the replaced rows superseded; no application flow deletes evidence or audits.
- An `other` organization may receive an application-specific supplemental requirement. It must be explained, audited, and must not mutate global config.
- Do not add OCR, AI decisions, facial recognition, regulator API calls, bulk decisions, persisted drafts, an admin requirement builder, or a generalized verification framework.
- Preserve development data. Never run `migrate:fresh`, `db:wipe`, destructive seeders, truncation, or destructive tests against the development database.
- Before any database-backed test, run `rg 'DB_DATABASE.+cc_db_test' phpunit.xml`; expect a match. Stop if the isolated database is not `cc_db_test`.
- Each task is a review checkpoint. Stage only its listed files, preserve unrelated marketplace work already in the tree, and commit only after focused checks pass.

---

## File Map and Delivery Order

| Area | Files and responsibility |
| --- | --- |
| Registry and schema | `config/connector_verification.php`, `ConnectorVerificationRequirements`, one forward migration, three evidence models, and Connector relationships/status constants. |
| Submission | Extend `StoreConnectorRequest`, `RegistrationController`, and `ConnectorRegistrationService`; add `ConnectorVerificationService` for snapshots, files, audits, row locks, and cleanup. |
| Registration UI | Replace the single-page registration form with a five-step Blade/Alpine workflow while retaining current Cavite city/barangay loading. |
| Applicant lifecycle | Extend the status page and add one resubmission request/route for legacy-pending, rejected, newly requested, and expired evidence. |
| Secure evidence | One streaming controller used by applicant and admin routes, with strict ownership/admin authorization, safe headers, and access audits. |
| Admin review | Thin verification controller/request objects for document decisions, permitted N/A exceptions, and custom requirements; refactor overall decisions into the service. |
| Admin UI | Extend existing Connector queue/detail views with the Payment Management visual baseline, grouped evidence, secure previews, checklist, notes, and audit timeline. |
| Expiration and notices | One scheduled command plus existing/new Connector notifications; no automatic suspension or purge job. |
| Public compatibility | Show only an optional verified logo and one truthful platform badge; retain every existing operational access boundary. |
| Release evidence | Focused and regression PHPUnit suites, Pint, Vite build, forward-migration rehearsal, privacy review, responsive/accessibility walkthrough, and staging smoke test. |

## Stable Lifecycle Values

Use model constants rather than repeating strings.

```php
// Connector verification_status
public const VERIFICATION_UNDER_REVIEW = 'under_review';
public const VERIFICATION_RESUBMISSION_REQUIRED = 'resubmission_required';
public const VERIFICATION_VERIFIED = 'verified';
public const VERIFICATION_LEGACY_VERIFIED = 'legacy_verified';
public const VERIFICATION_REVERIFICATION_REQUIRED = 'reverification_required';
public const VERIFICATION_REJECTED = 'rejected';

// ConnectorVerificationRequirement status
public const STATUS_PENDING = 'pending';
public const STATUS_SATISFIED = 'satisfied';
public const STATUS_REJECTED = 'rejected';
public const STATUS_EXPIRED = 'expired';
public const STATUS_NOT_APPLICABLE = 'not_applicable';
public const STATUS_SUPERSEDED = 'superseded';

// ConnectorVerificationDocument status
public const STATUS_PENDING = 'pending';
public const STATUS_ACCEPTED = 'accepted';
public const STATUS_REJECTED = 'rejected';
public const STATUS_EXPIRED = 'expired';
public const STATUS_SUPERSEDED = 'superseded';
```

Interpret a nullable `verification_status` on a pre-migration, non-verified Connector as “verification evidence not yet submitted”; do not invent another stored lifecycle value.

---

### Task 1: Add the requirement registry and forward-only evidence schema

**Files:**
- Create: `config/connector_verification.php`
- Create: `app/Support/ConnectorVerificationRequirements.php`
- Create: `database/migrations/2026_10_07_000001_add_connector_verification_workflow.php`
- Create: `app/Models/ConnectorVerificationRequirement.php`
- Create: `app/Models/ConnectorVerificationDocument.php`
- Create: `app/Models/ConnectorVerificationAudit.php`
- Modify: `app/Models/Connector.php`
- Modify: `app/Models/ConnectorReview.php`
- Test: `tests/Unit/Support/ConnectorVerificationRequirementsTest.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationSchemaTest.php`

**Interfaces:**

```php
final class ConnectorVerificationRequirements
{
    public function organizationTypes(): array;
    public function isCompatible(string $category, string $organizationType): bool;
    public function requirementsFor(string $category, string $organizationType): array;
    public function snapshotFor(string $category, string $organizationType): array;
}
```

`requirementsFor()` returns normalized arrays with these keys: `key`, `label`, `guidance`, `evidence_category`, `required`, `rule_group`, `rule_mode`, `minimum_evidence_count`, `maximum_evidence_count`, `accepted_mime_types`, `max_kilobytes`, `metadata_fields`, `expires`, `manual_not_applicable_allowed`, `issuing_authority`, `source_url`, and `source_reviewed_on`. `snapshotFor()` returns the same data as plain arrays suitable for JSON and database rows.

- [ ] **Step 1: Write the failing registry test.** Assert all fourteen approved organization types exist; every category/type pair is explicitly allowed or denied; every resolved application includes representative identity and representative authority; each required group is satisfiable; stable keys are unique; official-source requirements contain authority, HTTPS URL, and `source_reviewed_on`; and every upload rule uses the approved MIME and size ceilings.

```php
public function test_every_type_resolves_complete_safe_requirements(): void
{
    $registry = app(ConnectorVerificationRequirements::class);

    $this->assertCount(14, $registry->organizationTypes());

    foreach (array_keys($registry->organizationTypes()) as $type) {
        $category = $this->compatibleCategoryFor($type);
        $requirements = collect($registry->requirementsFor($category, $type));

        $this->assertSame($requirements->count(), $requirements->pluck('key')->unique()->count());
        $this->assertTrue($requirements->contains('evidence_category', 'representative_identity'));
        $this->assertTrue($requirements->contains('evidence_category', 'representative_authority'));
        $this->assertTrue($requirements->every(
            fn (array $item) => $item['max_kilobytes'] <= 10240
                && count(array_diff($item['accepted_mime_types'], [
                    'application/pdf', 'image/jpeg', 'image/png', 'image/webp',
                ])) === 0
        ));
    }
}
```

- [ ] **Step 2: Run the registry test and verify the expected failure.** Run `php artisan test tests/Unit/Support/ConnectorVerificationRequirementsTest.php`; expect failure because the config and resolver do not exist.

- [ ] **Step 3: Implement the smallest versioned registry.** Use one config file and one resolver; do not add interfaces or a database editor. Encode the taxonomy and regulatory matrix from the approved design. Define reusable requirements once, then map organization types to stable keys and compatible categories.

```php
return [
    'version' => '2026-10-07',
    'evidence_disk' => 'local',
    'logo_disk' => 'public',
    'expiration_warning_days' => [30, 7],
    'organization_types' => [
        'barangay_lgu' => ['label' => 'Barangay / LGU', 'categories' => ['government']],
        'other' => ['label' => 'Other', 'categories' => [
            'government', 'ngo', 'community_based_organization',
            'school_educational_institution', 'health_organization',
            'advocacy_group', 'other',
        ]],
    ],
    'requirements' => [
        'representative_identity' => [
            'evidence_category' => 'representative_identity',
            'required' => true,
            'rule_group' => 'representative_identity',
            'rule_mode' => 'all',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 2,
            'accepted_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'max_kilobytes' => 10240,
            'metadata_fields' => [],
            'expires' => false,
            'manual_not_applicable_allowed' => false,
        ],
        'representative_authority' => [
            'evidence_category' => 'representative_authority',
            'required' => true,
            'rule_group' => 'representative_authority',
            'rule_mode' => 'all',
            'minimum_evidence_count' => 1,
            'maximum_evidence_count' => 3,
            'accepted_mime_types' => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
            'max_kilobytes' => 10240,
            'metadata_fields' => ['issuer', 'issue_date'],
            'expires' => false,
            'manual_not_applicable_allowed' => false,
        ],
    ],
    'type_requirements' => [
        'barangay_lgu' => ['barangay_identity', 'representative_identity', 'representative_authority'],
        'other' => ['other_organization_identity', 'representative_identity', 'representative_authority'],
    ],
];
```

The snippet fixes the config shape; transcribe every organization-type and stable-key entry from the complete table below. Use `all`, `any`, and `minimum` only as rule modes. Keep optional sector authorization separate and display it only when selected activity/category makes it applicable. Do not make a generic health license mandatory from `category = health_organization` alone.

Use this exact type-to-requirement map; labels and guidance follow the approved regulatory matrix:

| Organization type | Compatible category baseline | Requirement keys |
| --- | --- | --- |
| `barangay_lgu` | `government` | `barangay_identity`, `representative_identity`, `representative_authority` |
| `government_agency` | `government` | `government_agency_identity`, optional `activity_specific_authority`, representative identity and authority |
| `public_educational_institution` | `school_educational_institution`, `government` | `public_school_identity`, minimum one of `deped_authority`, `ched_authority`, or `tesda_program_registration`, plus representative identity and authority |
| `private_basic_education_institution` | `school_educational_institution` | minimum one of `sec_registration` or `dti_registration`, `deped_authority`, representative identity and authority |
| `higher_education_institution` | `school_educational_institution` | minimum one of `sec_registration`, `institution_charter`, or `government_institution_identity`, `ched_authority`, representative identity and authority |
| `technical_vocational_institution` | `school_educational_institution` | `technical_vocational_identity`, `tesda_program_registration`, representative identity and authority |
| `ngo_nonprofit_foundation` | `ngo`, `advocacy_group` | `sec_nonstock_registration`, optional `activity_specific_authority`, representative identity and authority |
| `corporation_private_company` | `health_organization`, `advocacy_group`, `other` | `sec_registration`, optional `activity_specific_authority`, representative identity and authority |
| `partnership` | `health_organization`, `advocacy_group`, `other` | `sec_partnership_registration`, optional `activity_specific_authority`, representative identity and authority |
| `cooperative` | `community_based_organization`, `other` | `cda_registration`, optional `cda_branch_or_activity_authority`, representative identity and authority |
| `social_welfare_agency` | `ngo`, `health_organization`, `advocacy_group` | `sec_nonstock_registration`, `dswd_crlto`, optional `dswd_program_accreditation`, representative identity and authority |
| `registered_community_association` | `community_based_organization`, `advocacy_group` | minimum one of `sec_registration`, `cda_registration`, or `other_registration`, optional `activity_specific_authority`, representative identity and authority |
| `informal_community_organization` | `community_based_organization`, `advocacy_group`, `other` | minimum two of `barangay_or_community_certification`, `organization_charter`, `officer_list`, and `current_activity_evidence`, plus representative identity and authority |
| `other` | any existing category | `other_organization_identity`, representative identity and authority; admin may add a supplemental requirement after submission |

- [ ] **Step 4: Write the failing schema/model test.** Assert the Connector and ConnectorReview extensions, new tables, casts, relationships, restrictive historical foreign keys, and legacy backfill. Create existing verified, pending, rejected, suspended, and withdrawn Connectors before executing the migration logic; assert only verified rows become `legacy_verified` and every operational status is unchanged.

```php
$this->assertTrue(Schema::hasColumns('connectors', [
    'organization_type', 'logo_path', 'verification_status',
    'current_submission_round', 'verification_submitted_at',
]));
$this->assertTrue(Schema::hasColumns('connector_verification_requirements', [
    'connector_id', 'requirement_key', 'evidence_category', 'label', 'guidance',
    'is_required', 'minimum_evidence_count', 'maximum_evidence_count',
    'definition_snapshot', 'source_snapshot', 'status',
    'learner_identity_verification_id', 'requested_by', 'superseded_at',
]));
$this->assertTrue(Schema::hasColumns('connector_verification_documents', [
    'connector_id', 'connector_verification_requirement_id', 'uploaded_by',
    'submission_round', 'disk', 'path', 'original_name', 'mime_type',
    'size_bytes', 'content_sha256', 'issuer', 'document_number', 'issue_date',
    'expiry_date', 'status', 'reviewed_by', 'reviewed_at',
    'applicant_rejection_reason', 'internal_review_note', 'superseded_at',
]));
$this->assertTrue(Schema::hasColumns('connector_verification_audits', [
    'connector_id', 'connector_verification_requirement_id',
    'connector_verification_document_id', 'actor_id', 'action',
    'previous_state', 'new_state', 'submission_round',
    'applicant_reason', 'internal_notes',
]));
```

- [ ] **Step 5: Run the schema test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorVerificationSchemaTest.php`; expect failure because the migration and models do not exist.

- [ ] **Step 6: Implement one incremental migration and the model surface.** Add indexes on `organization_type`, `verification_status`, `(name, city_code, barangay_code, status)`, `(connector_id, status)`, `(connector_verification_requirement_id, status)`, `(connector_id, submission_round)`, and `content_sha256`. Make `(connector_id, requirement_key, superseded_at)` queryable but do not use a nullable-column unique key as the only duplicate guard. Use JSON casts for definition/source and audit states; date/datetime casts for issue, expiry, submission, review, and supersession fields. Add `verificationRequirements`, `verificationDocuments`, and `verificationAudits` relationships to `Connector`.

Add nullable `reason_code`, `internal_notes`, and `submission_round` to `connector_reviews`. Use nullable foreign keys with `nullOnDelete()` for actors/reviewers and restrictive foreign keys for the Connector and evidence records so user cleanup cannot erase verification history.

Backfill only:

```php
DB::table('connectors')
    ->where('status', 'verified')
    ->whereNull('verification_status')
    ->update(['verification_status' => Connector::VERIFICATION_LEGACY_VERIFIED]);
```

Do not synthesize evidence rows for existing Connectors. The guarded `down()` may reverse an empty installation, but must refuse to drop evidence-bearing tables or populated new columns.

- [ ] **Step 7: Run focused foundation checks.** Run:

```text
php artisan test tests/Unit/Support/ConnectorVerificationRequirementsTest.php tests/Feature/Connectors/ConnectorVerificationSchemaTest.php tests/Feature/Connectors/ConnectorSchemaTest.php
vendor/bin/pint --test config/connector_verification.php app/Support/ConnectorVerificationRequirements.php app/Models/Connector.php app/Models/ConnectorReview.php
vendor/bin/pint --test app/Models/ConnectorVerificationRequirement.php app/Models/ConnectorVerificationDocument.php app/Models/ConnectorVerificationAudit.php database/migrations/2026_10_07_000001_add_connector_verification_workflow.php
```

Expect all pass and no changes to existing Connector operational statuses.

- [ ] **Step 8: Commit.** Stage only Task 1 files and commit with `git commit -m 'feat: add connector verification foundation'`.

---

### Task 2: Enforce and persist initial verification submissions

**Files:**
- Create: `app/Services/Connectors/ConnectorVerificationService.php`
- Modify: `app/Services/Connectors/ConnectorRegistrationService.php`
- Modify: `app/Http/Requests/Connector/StoreConnectorRequest.php`
- Modify: `app/Http/Controllers/Connector/RegistrationController.php`
- Modify: `app/Notifications/Connectors/ConnectorApplicationSubmittedNotification.php`
- Modify: `tests/Feature/Connectors/ConnectorTestHelpers.php`
- Modify: `tests/Feature/Connectors/ConnectorRegistrationTest.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php`

**Interfaces:**

```php
// Keep the current public entry point.
ConnectorRegistrationService::register(User $applicant, array $data): Connector;

// Owns the transaction, file cleanup, snapshots, and initial audit.
ConnectorVerificationService::registerInitialApplication(
    User $applicant,
    array $data,
): Connector;
```

The request payload uses `logo`, `organization_type`, `reuse_representative_identity`, `confirm_accuracy`, and:

```php
'evidence' => [
    'stable_requirement_key' => [
        'files' => [UploadedFile::fake()->create('evidence.pdf')],
        'issuer' => 'Configured only when required',
        'document_number' => null,
        'issue_date' => '2026-10-07',
        'expiry_date' => null,
    ],
],
```

- [ ] **Step 1: Write failing submission tests.** Cover: incompatible category/type; missing required or any-of evidence; unknown requirement keys; excess uploads; unsupported MIME or oversized files; metadata supplied when not configured; missing authority evidence; valid identity-case reuse without copying files; duplicate active organization; private document storage; public logo storage; requirement snapshots; hashes and detected MIME; initial audit/review rows; pending owner membership; and cleanup of only newly stored files after an injected database failure.

```php
Storage::fake('local');
Storage::fake('public');
Notification::fake();

$response = $this->actingAs($applicant)->post(
    route('connectors.store'),
    $this->connectorVerificationPayload('ngo_nonprofit_foundation')
);

$connector = Connector::query()->sole();
$response->assertRedirect(route('connector.status', $connector));
$this->assertSame('pending', $connector->status);
$this->assertSame(Connector::VERIFICATION_UNDER_REVIEW, $connector->verification_status);
$this->assertSame(1, $connector->current_submission_round);
$this->assertDatabaseHas('connector_verification_documents', [
    'connector_id' => $connector->id,
    'submission_round' => 1,
    'disk' => 'local',
    'status' => ConnectorVerificationDocument::STATUS_PENDING,
]);
```

- [ ] **Step 2: Run the tests and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorRegistrationTest.php tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php`; expect missing rules, service, fields, and evidence rows.

- [ ] **Step 3: Implement authoritative dynamic validation.** Keep current name/contact/Cavite/unique-email rules. Resolve the registry again in `StoreConnectorRequest::rules()` and add an `after()` validator that:

  - rejects an unrecognized or incompatible organization type;
  - compares submitted keys with the resolved allow-list;
  - evaluates `all`, `any`, and `minimum` groups;
  - validates each configured file count, detected MIME, and size;
  - permits only configured metadata fields;
  - requires `confirm_accuracy`;
  - permits representative identity reuse only when the current account owns a non-superseded approved `LearnerIdentityVerification`; and
  - still requires representative authority when identity is reused.

Alpine-provided requirement data is presentation only. Never accept labels, required flags, limits, source URLs, or status from the browser.

- [ ] **Step 4: Implement atomic registration and storage cleanup.** Keep `ConnectorRegistrationService::register()` as the controller entry point and make it delegate to `ConnectorVerificationService::registerInitialApplication()`. Let the verification service own one database transaction and all newly written path tracking. Create the pending Connector, default Owner role, pending primary membership, requirement snapshots, document rows, upload audits, and the initial `ConnectorReview`. Set:

```php
[
    'organization_type' => $data['organization_type'],
    'verification_status' => Connector::VERIFICATION_UNDER_REVIEW,
    'current_submission_round' => 1,
    'verification_submitted_at' => now(),
]
```

Store evidence with generated names under `connector-verifications/{connector-id}/round-1/` on `local`. Store the optional logo under `connector-logos/{connector-id}/` on `public`. Persist the detected MIME, byte count, SHA-256 hash, original basename, and only configured metadata. Reject an active duplicate by organization email when present, otherwise use the indexed case-insensitive name plus Cavite city/barangay lookup with `lockForUpdate()` among pending, verified, or suspended Connectors.

Track every newly written `[disk, path]` pair. If storage or the transaction fails, delete those paths and rethrow; never delete a pre-existing path. Register applicant/admin notifications with `DB::afterCommit()`, and log notification transport failure without rolling back the successful submission.

- [ ] **Step 5: Prove identity reuse does not copy evidence.** Create an approved, non-superseded `LearnerIdentityVerification` for the applicant. Submit with reuse enabled, assert the representative-identity requirement references that row, and assert the Connector has no copied document for that requirement. Repeat with a rejected, superseded, or another user's case and expect validation failure.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/ConnectorRegistrationTest.php tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php tests/Feature/Connectors/ConnectorNotificationTest.php
vendor/bin/pint --test app/Http/Requests/Connector/StoreConnectorRequest.php app/Http/Controllers/Connector/RegistrationController.php app/Services/Connectors/ConnectorRegistrationService.php app/Services/Connectors/ConnectorVerificationService.php
```

Expect all pass; inspect `Storage::fake('local')` assertions to confirm no public evidence path exists.

- [ ] **Step 7: Commit.** Stage only Task 2 files and commit with `git commit -m 'feat: submit connector verification evidence'`.

---

### Task 3: Build the five-step registration experience

**Files:**
- Modify: `app/Http/Controllers/Connector/RegistrationController.php`
- Modify: `resources/views/connectors/register.blade.php`
- Test: `tests/Feature/Connectors/ConnectorRegistrationStepperTest.php`

**Interface:** `RegistrationController::create()` adds `organizationTypes` and a presentation-safe `requirementCatalog` from `ConnectorVerificationRequirements`. The catalog may contain labels, guidance, required flags, metadata field names, issuer/source labels, and client display limits; it must not replace server validation.

- [ ] **Step 1: Write the failing UI test.** Assert the authenticated form has multipart encoding, five named steps, `Step 1 of 5`, category and organization-type controls, existing Cavite city/barangay behavior, separate logo and evidence sections, representative account summary, accuracy confirmation, visible labels, inline error anchors, Back/Continue controls, and a final Submit button. Assert there is no public evidence URL, no persisted-draft endpoint, and no modal containing the full form.

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorRegistrationStepperTest.php`; expect failure because the existing page is a single form.

- [ ] **Step 3: Implement the Blade/Alpine stepper in the existing view.** Use one `x-data` component and no new JavaScript dependency. Render all server fields in one form:

1. Organization type: existing category, new organization type, and a short explanation of the distinction.
2. Organization information: existing name, email, phone, website, description, Cavite city, barangay, and address.
3. Organization logo: optional JPEG/PNG/WebP, 2 MB message, local preview, replace/remove, and explicit text that it is not verification evidence.
4. Verification evidence: registry requirements grouped as organization identity, regulatory eligibility, representative identity, representative authority, and supporting evidence.
5. Representative and confirmation: signed-in account, identity reuse state, authority reminder, submission summary, and accuracy confirmation.

Use the current `loadBarangays` behavior rather than replacing it. On validation redirect, open the first step containing an error and retain scalar `old()` values. Browser security prevents restoring file inputs, so show a clear request to reselect rejected uploads.

- [ ] **Step 4: Add progressive and accessible behavior.** Show visible step text and an ordered progress indicator with `aria-current`. Continue validates only the current client-side presentation needs; final server submission remains authoritative. Focus the first heading or invalid field after a step change, keep action targets at least 40–44 px, use buttons for navigation, expose required/optional text, and never rely on color alone.

When category or organization type changes, compare the old and new compatible requirement keys. Preserve compatible file selections where the browser permits it; before clearing incompatible selections, show an inline confirmation. Revoke logo object URLs after replacement/removal. At mobile widths, use one column and sticky final-step actions only if they do not hide content.

- [ ] **Step 5: Verify server-error and no-JavaScript safety.** Add a test posting invalid data and asserting errors map to their named inputs. Keep every step's fields in the HTML and use Alpine only for presentation, so validation messages and form semantics remain inspectable even if scripts fail.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/ConnectorRegistrationStepperTest.php tests/Feature/Connectors/ConnectorRegistrationTest.php tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php
npm run build
```

Expect PHPUnit and the Vite production build to pass.

- [ ] **Step 7: Commit.** Stage only Task 3 files and commit with `git commit -m 'feat: add connector verification stepper'`.

---

### Task 4: Add applicant status, legacy completion, and partial resubmission

**Files:**
- Create: `app/Http/Requests/Connector/ResubmitConnectorVerificationRequest.php`
- Modify: `app/Services/Connectors/ConnectorVerificationService.php`
- Modify: `app/Http/Controllers/Connector/RegistrationController.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/connectors/status.blade.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php`
- Modify: `tests/Feature/Connectors/ConnectorWithdrawalTest.php`

**Route and service:**

```php
Route::post('/connector/{connector}/verification/resubmit', [RegistrationController::class, 'resubmit'])
    ->name('connector.verification.resubmit');

ConnectorVerificationService::resubmit(
    Connector $connector,
    User $applicant,
    array $submission,
    int $expectedRound,
): Connector;
```

- [ ] **Step 1: Write failing lifecycle tests.** Cover safe status visibility, existing pending application completion, rejected-document replacement, a newly requested supplemental requirement, expired evidence replacement, accepted-document preservation, one round increment per submission, duplicate-hash rejection, stale-round conflict, unauthorized ordinary-member denial, and internal-note non-disclosure.

```php
$acceptedId = $accepted->id;
$rejectedId = $rejected->id;

$this->actingAs($connector->primaryRepresentative)
    ->post(route('connector.verification.resubmit', $connector), [
        'submission_round' => $connector->current_submission_round,
        'evidence' => [
            $rejected->requirement->requirement_key => [
                'files' => [UploadedFile::fake()->create('replacement.pdf', 200, 'application/pdf')],
            ],
        ],
    ])
    ->assertRedirect(route('connector.status', $connector));

$this->assertSame(
    ConnectorVerificationDocument::STATUS_ACCEPTED,
    ConnectorVerificationDocument::findOrFail($acceptedId)->status,
);
$this->assertSame(
    ConnectorVerificationDocument::STATUS_SUPERSEDED,
    ConnectorVerificationDocument::findOrFail($rejectedId)->status,
);
```

- [ ] **Step 2: Run the tests and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php tests/Feature/Connectors/ConnectorWithdrawalTest.php`; expect missing route, request, service method, and status presentation.

- [ ] **Step 3: Implement applicant authorization and dynamic rules.** Authorize only the creator or primary representative. Allow resubmission when:

  - an old pending Connector has nullable `verification_status`;
  - `verification_status = resubmission_required`; or
  - operational status is verified and `verification_status = reverification_required`.

Reject under-review, permanently rejected, withdrawn, and suspended submissions. For a legacy pending Connector, require organization type and all resolved initial evidence. Otherwise accept files only for rejected, expired, or administrator-requested pending requirements. Re-evaluate each snapshotted rule; do not replace snapshots from current config during a normal resubmission.

- [ ] **Step 4: Implement immutable partial replacement.** In one row-locked transaction:

  1. Compare `expectedRound` to the locked Connector and throw a 409 conflict on mismatch.
  2. Reject a SHA-256 already present for the same active requirement.
  3. Increment `current_submission_round` exactly once.
  4. Mark only the replaced rejected/expired document rows `superseded` with `superseded_at`.
  5. Create new pending document rows in the new round.
  6. Keep accepted rows and their requirement satisfaction unchanged.
  7. Set submitted requirements back to pending and the Connector to `under_review`.
  8. Create replacement/submission audits and an overall review history row.
  9. Delete only newly written files if the operation fails.
  10. Notify administrators after commit.

For an old pending Connector, snapshot current requirements first, then follow the same upload path. Never retrofit evidence onto existing verified/legacy-verified Connectors automatically.

- [ ] **Step 5: Rebuild the status page around two status dimensions.** Show organization identity, the public logo only when operationally verified, operational status, verification status, grouped requirement progress, applicant-facing reasons, safe review history, and the next action. Render upload inputs only for eligible requirements. Never select or render `internal_review_note` or audit `internal_notes` in the applicant response.

Keep withdrawal available under the existing rules. A regular member may see only the existing safe operational summary; only the creator/primary representative gets evidence detail or resubmission controls.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php tests/Feature/Connectors/ConnectorWithdrawalTest.php tests/Feature/Connectors/ConnectorDashboardAccessTest.php
php artisan route:list --name=connector.verification
```

Expect all pass and exactly one applicant resubmission route.

- [ ] **Step 7: Commit.** Stage only Task 4 files and commit with `git commit -m 'feat: resubmit connector verification evidence'`.

---

### Task 5: Stream private evidence through authorized, audited routes

**Files:**
- Create: `app/Http/Controllers/Connector/VerificationDocumentController.php`
- Modify: `app/Services/Connectors/ConnectorVerificationService.php`
- Modify: `routes/connector.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationDocumentAccessTest.php`

**Routes:**

```php
GET /connector/{connector}/verification/documents/{document}
    -> connector.verification.documents.show

GET /admin/connectors/{connector}/verification/documents/{document}
    -> admin.connectors.verification.documents.show
```

Both routes use the same invokable controller. `?disposition=inline` previews and `?disposition=attachment` downloads; reject any other value.

- [ ] **Step 1: Write failing access tests.** Assert the primary applicant and an administrator can stream a current or historical document; an ordinary Connector member, unrelated authenticated user, and guest cannot; a document belonging to another Connector returns 404; a missing private file returns 404 without its path; response headers are private/no-store and nosniff; the filename is sanitized; and successful preview/download actions create distinct audits.

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorVerificationDocumentAccessTest.php`; expect missing routes/controller.

- [ ] **Step 3: Implement strict binding and authorization.** Resolve the Connector and document, then return 404 unless `document.connector_id === connector.id`. Permit only:

  - Connector creator;
  - primary representative; or
  - a user inside the existing admin route/middleware boundary.

Do not grant access from ordinary Connector membership or Connector role permissions. Do not expose disk, path, hash, internal notes, or reviewer-only audit state in a JSON response.

- [ ] **Step 4: Reuse the existing secure-response pattern.** Confirm the stored MIME is one of the four allowed types and the private file exists. Stream through `BinaryFileResponse` using a sanitized basename and the recorded detected MIME. Set:

```php
[
    'X-Content-Type-Options' => 'nosniff',
    'Cache-Control' => 'private, no-store, max-age=0',
    'Pragma' => 'no-cache',
]
```

Use Symfony's response-header disposition helper rather than manually constructing `Content-Disposition`. Record `document_previewed` or `document_downloaded` only after authorization and file existence checks succeed.

- [ ] **Step 5: Prove there is no public bypass.** Assert `Storage::disk('public')->missing($document->path)`, the applicant/admin pages point only to the controller route, and a direct `/storage/...` request cannot resolve the evidence file.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/ConnectorVerificationDocumentAccessTest.php tests/Feature/Identity/LearnerIdentityAccessTest.php
php artisan route:list --name=verification.documents
```

Expect authorization, 404, header, and audit assertions to pass.

- [ ] **Step 7: Commit.** Stage only Task 5 files and commit with `git commit -m 'feat: secure connector verification evidence'`.

---

### Task 6: Add document-level admin review and supplemental requirements

**Files:**
- Create: `app/Http/Controllers/Admin/ConnectorVerificationController.php`
- Create: `app/Http/Requests/Admin/ReviewConnectorVerificationDocumentRequest.php`
- Create: `app/Http/Requests/Admin/ResolveConnectorVerificationRequirementRequest.php`
- Create: `app/Http/Requests/Admin/RequestConnectorVerificationEvidenceRequest.php`
- Modify: `app/Services/Connectors/ConnectorVerificationService.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php`

**Routes and service methods:**

```php
POST /admin/connectors/{connector}/verification/documents/{document}/review
    -> admin.connectors.verification.documents.review
POST /admin/connectors/{connector}/verification/requirements/{requirement}/resolve
    -> admin.connectors.verification.requirements.resolve
POST /admin/connectors/{connector}/verification/request-evidence
    -> admin.connectors.verification.request-evidence

ConnectorVerificationService::reviewDocument(
    Connector $connector,
    ConnectorVerificationDocument $document,
    User $admin,
    string $decision,
    int $expectedRound,
    ?string $applicantReason,
    ?string $internalNote,
): ConnectorVerificationDocument;

ConnectorVerificationService::markRequirementNotApplicable(
    Connector $connector,
    ConnectorVerificationRequirement $requirement,
    User $admin,
    int $expectedRound,
    string $reason,
    ?string $internalNote,
): ConnectorVerificationRequirement;

ConnectorVerificationService::requestSupplementalRequirement(
    Connector $connector,
    User $admin,
    array $definition,
    int $expectedRound,
): ConnectorVerificationRequirement;
```

- [ ] **Step 1: Write failing document-review tests.** Cover admin middleware, cross-Connector 404, accept, reject with mandatory applicant reason, optional internal note separation, stale round 409, finalized/superseded document conflict, permitted N/A resolution with reason, forbidden N/A on a mandatory non-exemptable requirement, custom evidence for `other`, custom evidence denial for every other type, audits, and post-commit notifications.

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php`; expect missing routes, requests, controller, and service methods.

- [ ] **Step 3: Implement thin requests and controller actions.** Use the existing admin route boundary. Validate:

  - `submission_round` as a required positive integer;
  - document decision as `accept` or `reject`;
  - an applicant-facing rejection reason for reject;
  - a separately bounded internal note;
  - an N/A reason for `not_applicable`;
  - custom label, applicant guidance, evidence category, one-to-five maximum uploads, approved MIME list, configured metadata field allow-list, and expiry applicability.

The controller must delegate and redirect back to `admin.connectors.show`; it must not perform state transitions itself.

- [ ] **Step 4: Implement row-locked document decisions.** Lock Connector, requirement, and document; verify all belong together, the Connector is under review, and the submitted round equals the current round. Only pending current documents may be decided. On accept, set reviewer/time and recompute the requirement from accepted evidence counts. On reject, store applicant/internal notes separately, set reviewer/time, and recompute the requirement. Keep the overall state under review until the administrator uses the explicit Request resubmission action in Task 7.

An accepted count at or above the snapshotted minimum satisfies that requirement even if an extra optional document is rejected. No action mutates a prior round or deletes a file.

- [ ] **Step 5: Implement the two requirement-level exceptions.**

  - N/A: allow only when `definition_snapshot.manual_not_applicable_allowed` is true; require a reason; save status and audit actor/reason.
  - Supplemental request: allow only when `organization_type = other`; create one application-scoped snapshot with key `custom_{uuid}`, `requested_by`, approved formats/limits, and pending status; set Connector to `resubmission_required`.

Neither action edits `config/connector_verification.php`.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php
php artisan route:list --name=admin.connectors.verification
```

Expect exact review, requirement resolution, supplemental request, and secure-document routes.

- [ ] **Step 7: Commit.** Stage only Task 6 files and commit with `git commit -m 'feat: review connector verification evidence'`.

---

### Task 7: Enforce the overall decision gate in one service

**Files:**
- Create: `app/Http/Requests/Admin/RequestConnectorResubmissionRequest.php`
- Modify: `app/Http/Requests/Admin/ApproveConnectorRequest.php`
- Modify: `app/Http/Requests/Admin/RejectConnectorRequest.php`
- Modify: `app/Http/Controllers/Admin/ConnectorController.php`
- Modify: `app/Services/Connectors/ConnectorVerificationService.php`
- Modify: `routes/admin.php`
- Modify: `tests/Feature/Connectors/AdminConnectorModerationTest.php`
- Test: `tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php`

**Service methods:**

```php
ConnectorVerificationService::approve(
    Connector $connector,
    User $admin,
    int $expectedRound,
    array $checklist,
    ?string $internalNote = null,
): Connector;

ConnectorVerificationService::requestResubmission(
    Connector $connector,
    User $admin,
    int $expectedRound,
    string $applicantReason,
    ?string $internalNote = null,
): Connector;

ConnectorVerificationService::permanentlyReject(
    Connector $connector,
    User $admin,
    int $expectedRound,
    string $reasonCode,
    string $applicantReason,
    ?string $internalNote = null,
): Connector;
```

- [ ] **Step 1: Write failing decision tests.** Assert direct approval fails when requirements are absent, pending, rejected, expired, under the required count, or in an unsatisfied any/minimum group. Assert it fails when any checklist item is false, when the round is stale, or when the Connector is not pending. Assert a valid approval activates only the primary representative's pending membership, sets both status dimensions correctly, records one review/audit, and preserves all evidence.

Also cover overall resubmission, permanent rejection, existing suspension behavior, repeated/finalized decisions, and a legacy verified Connector remaining operational without evidence.

- [ ] **Step 2: Run the tests and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/AdminConnectorModerationTest.php tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php`; the existing direct approval test must fail until the gate is implemented.

- [ ] **Step 3: Validate the exact approval checklist.** Require accepted booleans for:

  - `organization_identity_established`;
  - `regulatory_evidence_resolved`;
  - `representative_identity_verified`;
  - `representative_authority_established`;
  - `required_evidence_complete`; and
  - `application_consistent`.

The checklist records admin attestation but never overrides computed evidence state.

- [ ] **Step 4: Move all overall mutations out of the controller.** Replace direct transactions in `Admin\ConnectorController::approve()` and `reject()` with service calls. Add `requestResubmission()`. Keep `suspend()` behavior compatible, but route its transaction through the same service so review history and status invariants stay centralized. Controllers validate, delegate, and redirect only.

- [ ] **Step 5: Implement the approval evaluator.** Under row locks, derive completion from snapshots:

  - every required `all` requirement is satisfied or has an allowed audited N/A;
  - each `any` group has at least one satisfied member;
  - each `minimum` group reaches its snapshotted threshold;
  - referenced learner identity remains approved and non-superseded;
  - representative authority is satisfied independently;
  - no required current document is pending, rejected, or expired; and
  - the submitted round equals the locked current round.

On success set operational status to `verified`, verification status to `verified`, review actor/time, clear rejection/suspension fields, activate the primary pending membership, create `ConnectorReview` with the round, and write an approval audit. Notify after commit.

- [ ] **Step 6: Implement the other overall decisions.** Request resubmission only when at least one requirement is already rejected, expired, or newly requested; save the safe overall guidance and internal note separately. Permanent rejection requires an allow-listed reason code plus applicant guidance, sets operational and verification status to `rejected`, and preserves all evidence. Suspension changes only operational status and retains the evidence state.

There is currently no Connector profile-edit endpoint for name, organization type, or primary representative. Do not add a dormant observer or a new edit feature. Document that any future critical-identity update must call the service to set `reverification_required` before saving; ordinary logo, description, website, and contact changes remain non-critical.

- [ ] **Step 7: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/AdminConnectorModerationTest.php tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php tests/Feature/Connectors/ConnectorDashboardAccessTest.php
php artisan route:list --name=admin.connectors
```

Expect the prior evidence-free approval path to be impossible while verified workspace access remains unchanged.

- [ ] **Step 8: Commit.** Stage only Task 7 files and commit with `git commit -m 'feat: gate connector verification decisions'`.

---

### Task 8: Build the administrator verification workspace

**Files:**
- Modify: `app/Http/Controllers/Admin/ConnectorController.php`
- Modify: `resources/views/admin/connectors/index.blade.php`
- Modify: `resources/views/admin/connectors/show.blade.php`
- Test: `tests/Feature/Connectors/AdminConnectorVerificationWorkspaceTest.php`

- [ ] **Step 1: Write the failing workspace test.** Create a Connector with accepted, pending, rejected, expired, and superseded evidence. Assert the detail page renders the approved section order, purpose-level summary cards, separate logo, representative identity state, requirement labels/statuses, safe source metadata, secure preview/download routes, applicant/internal note inputs, exact checklist labels, submission round, decision controls, and audit history. Assert no storage path, document hash, or raw internal state is printed.

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/AdminConnectorVerificationWorkspaceTest.php`; expect the current basic detail page to miss the evidence workspace.

- [ ] **Step 3: Load the workspace without N+1 queries.** In `show()`, eager-load primary representative and current approved identity case, current and superseded requirements, documents with uploader/reviewer, reviews, and audits with actor. Order evidence by category, requirement, round descending, and upload order. Keep all fields server-derived.

Extend `index()` with verification-status counts and simple query-string filters for operational status, verification status, and organization type. Preserve the existing admin authorization boundary and current Connector links.

- [ ] **Step 4: Implement the approved page hierarchy.**

1. Back link, organization identity, submission date, and both statuses.
2. Summary cards for organization identity, regulatory eligibility, representative authority, and completion.
3. Organization details with the optional logo visually separate.
4. Representative account and identity state.
5. Requirements grouped by evidence purpose.
6. Authorized image/PDF preview and download controls.
7. Six-item approval checklist.
8. Approve, Request resubmission, Permanently reject, and existing suspension controls.
9. Chronological audit/review history.

Each requirement shows required/optional/N/A, its textual state, issuer and relevant dates, applicant guidance, decision controls, applicant-facing reason, and a separate internal note. Show superseded files as history, never as the default current preview.

- [ ] **Step 5: Apply `design.md` interaction rules.** Reuse the Payment Management gray/white card shell, spacing, typography, chips, and brand-purple primary action. Use the learner identity review dialog pattern for compact confirmation dialogs only. Give dialogs an accessible name, initial focus, Escape/Cancel behavior, focus return, and explicit destructive-action text. Do not use color alone, place no full review form in a modal, and add no bulk evidence action.

Show the N/A action only for a snapshotted exemptable requirement. Show supplemental evidence only for `other`. Include `submission_round` as a hidden value in every mutation form.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/AdminConnectorVerificationWorkspaceTest.php tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php
npm run build
```

Expect PHPUnit and Vite to pass.

- [ ] **Step 7: Commit.** Stage only Task 8 files and commit with `git commit -m 'feat: add connector verification workspace'`.

---

### Task 9: Deliver safe in-app and email notifications

**Files:**
- Create: `app/Notifications/Connectors/ConnectorVerificationActionRequiredNotification.php`
- Modify: `app/Notifications/Connectors/ConnectorApplicationSubmittedNotification.php`
- Modify: `app/Notifications/Connectors/ConnectorModerationDecisionNotification.php`
- Modify: `app/Services/Connectors/ConnectorRegistrationService.php`
- Modify: `app/Services/Connectors/ConnectorVerificationService.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationNotificationTest.php`
- Modify: `tests/Feature/Connectors/ConnectorNotificationTest.php`

Use the existing `emails.moderation-status` template. Do not create another email layout.

- [ ] **Step 1: Write failing notification tests.** Assert this matrix:

| Event | Applicant | Administrators |
| --- | --- | --- |
| New application | database | database |
| Resubmission | database | database |
| Supplemental requirement | mail + database | database audit context only |
| Required document rejected and resubmission requested | mail + database | none |
| Approval | mail + database | none |
| Permanent rejection | mail + database | none |
| Re-verification required | mail + database | database |
| Configured expiry warning | mail + database | none |

Also assert action URLs use named routes, callbacks occur only after successful state persistence, and payloads contain no filename, path, hash, document number, internal note, or private source URL.

- [ ] **Step 2: Run the tests and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorNotificationTest.php tests/Feature/Connectors/ConnectorVerificationNotificationTest.php`; expect missing events and mail channels.

- [ ] **Step 3: Extend the two existing notifications.** Keep `ConnectorApplicationSubmittedNotification` for initial submission and resubmission, with an explicit allow-listed event value that changes safe title/message/type. Keep `ConnectorModerationDecisionNotification` for approval and permanent rejection and add the mail channel using the configured verification mailer.

- [ ] **Step 4: Add one action-required notification.** Support only `supplemental_requirement`, `document_rejected`, `reverification_required`, and `expiration_warning`. Dispatch `document_rejected` when the explicit Request resubmission decision exposes the rejected requirement to the applicant, not while the administrator is still reviewing other documents. Build copy from the event and safe Connector/requirement labels; do not pass a document model into notification serialization.

- [ ] **Step 5: Dispatch after commit and isolate transport failures.** Collect notification intents during the transaction, then deliver after the successful outer service transaction. Wrap delivery in logging so a mail/database channel failure is visible but cannot roll back a persisted application or moderation decision. Do not log private evidence metadata.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/ConnectorNotificationTest.php tests/Feature/Connectors/ConnectorVerificationNotificationTest.php tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php
vendor/bin/pint --test app/Notifications/Connectors app/Services/Connectors/ConnectorRegistrationService.php app/Services/Connectors/ConnectorVerificationService.php
```

Expect all notification matrix and safe-payload assertions to pass.

- [ ] **Step 7: Commit.** Stage only Task 9 files and commit with `git commit -m 'feat: notify connector verification events'`.

---

### Task 10: Expire configured evidence without automatic suspension

**Files:**
- Create: `app/Console/Commands/ExpireConnectorVerificationDocuments.php`
- Modify: `app/Services/Connectors/ConnectorVerificationService.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationExpirationTest.php`

**Interface and command:**

```php
ConnectorVerificationService::sendExpirationWarnings(CarbonInterface $asOf): int;
ConnectorVerificationService::expireDueDocuments(CarbonInterface $asOf): int;

php artisan connectors:expire-verification-documents
```

- [ ] **Step 1: Write failing time-controlled tests.** Cover 30-day and 7-day configured warnings exactly once, a due accepted document becoming expired, an alternative accepted document keeping an any-of requirement satisfied, a foundational non-expiring document remaining accepted, a verified Connector moving only to `reverification_required`, a pending Connector moving to `resubmission_required`, audit rows, notifications, and command idempotency.

```php
Carbon::setTestNow('2026-10-07 01:00:00');

$this->artisan('connectors:expire-verification-documents')->assertSuccessful();

$this->assertSame('verified', $connector->fresh()->status);
$this->assertSame(
    Connector::VERIFICATION_REVERIFICATION_REQUIRED,
    $connector->fresh()->verification_status,
);
```

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorVerificationExpirationTest.php`; expect missing command/service behavior.

- [ ] **Step 3: Implement warning and expiration passes.** Select only active accepted documents whose snapshotted definition has `expires = true` and an expiry date. Process in bounded chunks and lock each Connector/requirement/document set before mutation.

For warnings, use the configured day offsets and an audit action containing the offset to prevent duplicate delivery. For expiry, mark the document expired, recompute its requirement and rule group, and change Connector verification state only when the required rule is no longer satisfied.

- [ ] **Step 4: Preserve the operational boundary.** For an operationally verified Connector, set `verification_status = reverification_required` and leave `status = verified`. For a pending Connector, set `resubmission_required`. Never call suspension, deactivate membership, revoke marketplace/library access, or alter seminars/community data from the expiration command.

- [ ] **Step 5: Schedule once daily.** Add:

```php
Schedule::command('connectors:expire-verification-documents')
    ->dailyAt('01:15')
    ->withoutOverlapping();
```

Do not add a purge command until an approved retention schedule exists.

- [ ] **Step 6: Run focused checks.** Run:

```text
php artisan test tests/Feature/Connectors/ConnectorVerificationExpirationTest.php tests/Feature/Connectors/ConnectorDashboardAccessTest.php
php artisan list --raw
```

Expect the command to be listed, idempotency assertions to pass, and verified operational access to remain active.

- [ ] **Step 7: Commit.** Stage only Task 10 files and commit with `git commit -m 'feat: expire connector verification evidence'`.

---

### Task 11: Preserve public truthfulness and Connector compatibility

**Files:**
- Modify: `resources/views/connectors/show.blade.php`
- Modify: `resources/views/connectors/partials/discovery-feed.blade.php`
- Modify: `tests/Feature/Connectors/ConnectorTestHelpers.php`
- Modify: `tests/Feature/Connectors/ConnectorHomeTest.php`
- Test: `tests/Feature/Connectors/ConnectorVerificationCompatibilityTest.php`

- [ ] **Step 1: Write failing public/privacy tests.** Assert a verified Connector with `verification_status = verified` or `legacy_verified` may display its optional logo and exactly this badge explanation:

> Verified by Conscious Connections based on submitted organizational and authorization documents.

Assert pending, rejected, suspended, and withdrawn Connectors do not render a public verification claim; an operationally verified Connector under re-verification shows neutral `Verification update in progress` text instead of the Verified badge; evidence categories, issuers, filenames, document numbers, expiry dates, private routes, and review notes never appear publicly; and a legacy verified Connector remains reachable wherever current behavior permits.

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test tests/Feature/Connectors/ConnectorVerificationCompatibilityTest.php tests/Feature/Connectors/ConnectorHomeTest.php`; expect missing logo/badge behavior.

- [ ] **Step 3: Add the minimum public presentation.** Render the logo only when `status = verified` and `logo_path` is present. Keep a text fallback. Render one platform verification badge only when the evidence state is `verified` or `legacy_verified`; use neutral update text for `reverification_required` or a replacement currently under review. Do not present separate public badges for identity, regulation, or representative authority.

- [ ] **Step 4: Prove existing access boundaries did not move.** Do not edit `ConnectorAccessService`. Update test helpers only where a test explicitly needs the new evidence state. Run:

```text
php artisan test tests/Feature/Connectors/ConnectorDashboardAccessTest.php tests/Feature/Connectors/ConnectorInvitationTest.php tests/Feature/Connectors/ConnectorMemberDirectoryAccessTest.php
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Feature/Connectors/ConnectorHomeTest.php tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php
```

Expect existing verified access, membership, seminar, community/profile discovery, and marketplace browse behavior to pass unchanged.

- [ ] **Step 5: Verify non-critical fields do not affect verification state.** Exercise existing safe logo/description/website/contact writes if any exist by implementation time and assert no re-verification transition. The current repository has no organization-name/type/primary-representative edit endpoint; do not add one in this task.

- [ ] **Step 6: Commit.** Stage only Task 11 files and commit with `git commit -m 'feat: present verified connector identity'`.

---

### Task 12: Document operations and execute release gates

**Files:**
- Create: `docs/connector-verification-operations.md`
- Modify only if a check exposes a defect: files from Tasks 1–11 and their focused tests

- [ ] **Step 1: Write the operating guide.** Document:

  - category versus organization type;
  - every stable requirement key, issuing authority, official source URL, and `source_reviewed_on`;
  - how to update registry version/config and why existing snapshots do not mutate;
  - applicant/admin access matrix;
  - private-disk and response-header controls;
  - submission rounds, stale-review handling, N/A exceptions, and supplemental requests;
  - expiry warnings, re-verification, and the explicit no-auto-suspension rule;
  - how a future critical organization-identity edit must call the verification service;
  - safe missing-file investigation without path disclosure;
  - audit events and safe notification payloads; and
  - rollback limits for evidence-bearing schema.

State that legal/government certification is not provided by the platform.

- [ ] **Step 2: Record the retention release gate.** Add an explicit unresolved production gate for an owner-approved organizational retention schedule covering raw evidence, logos, audits, legal holds, backups, and secure disposal. Do not invent a duration and do not implement automatic purge. Production enablement remains blocked until the decision, responsible owner, and approved date are recorded.

- [ ] **Step 3: Run automated verification on the isolated test database.** First verify `cc_db_test` in `phpunit.xml`, then run:

```text
php artisan test tests/Unit/Support/ConnectorVerificationRequirementsTest.php
php artisan test tests/Feature/Connectors/ConnectorVerificationSchemaTest.php tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php tests/Feature/Connectors/ConnectorRegistrationStepperTest.php
php artisan test tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php tests/Feature/Connectors/ConnectorVerificationDocumentAccessTest.php tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php
php artisan test tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php tests/Feature/Connectors/AdminConnectorVerificationWorkspaceTest.php tests/Feature/Connectors/ConnectorVerificationNotificationTest.php
php artisan test tests/Feature/Connectors/ConnectorVerificationExpirationTest.php tests/Feature/Connectors/ConnectorVerificationCompatibilityTest.php
php artisan test
vendor/bin/pint --test
npm run build
git diff --check
```

Expect all commands to pass. Do not call the feature complete if the full suite or build is skipped.

- [ ] **Step 4: Verify routes and schema without touching development data.** Run:

```text
php artisan route:list --name=connector.verification
php artisan route:list --name=admin.connectors.verification
php artisan migrate:status --env=testing
```

Rehearse the forward migration on an isolated recent-data copy, not the development database. Verify all prior Connector status counts, memberships, roles, seminars, community records, subscriptions, module entitlements, reviews, and legacy verified access before and after.

- [ ] **Step 5: Complete manual browser and accessibility QA.** On desktop and mobile, walk through each organization type, category compatibility, logo preview/removal, Cavite address loading, all/any/minimum evidence, identity reuse, server errors, applicant status, partial resubmission, admin preview/download, accept/reject/N/A/custom request, approval gate, rejection, expiration, and legacy verified display.

Use keyboard-only navigation and inspect focus order/return, dialog Escape/Cancel, labels, error association, status text, target size, PDF/image fallback, responsive overflow, and zoom. Confirm an ordinary member and unrelated user cannot obtain evidence and inspect private/no-store/nosniff headers in the browser.

- [ ] **Step 6: Complete staging gates.** Back up staging, apply the normal forward migration, run the scheduled command once, smoke-test registration through approval, confirm mail/database notifications, verify authorized evidence streaming and unauthorized denial, then confirm existing Connector dashboard, membership, seminars, community, subscriptions, and marketplace flows.

- [ ] **Step 7: Produce the release report.** Separate:

  - implemented scope;
  - passing automated checks with exact commands/results;
  - completed manual QA;
  - completed migration/staging evidence; and
  - blocked or unrun gates, especially retention approval.

Static checks alone are not completion evidence.

- [ ] **Step 8: Commit.** Stage the operations guide and any verified defect fixes only; commit with `git commit -m 'docs: add connector verification operations guide'`.
