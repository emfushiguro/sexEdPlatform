# Guardian Verification UI Refinements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make guardian identity uploads accept the required National ID and Passport back images, make dependent verification review view-only at the queue level with accurate two-sided guardian ID previews, and make relationship resubmission evidence visually previewable.

**Architecture:** Keep the existing protected document routes and verification services. The upload form will continue to derive its back-side requirement from `config/guardian_identity.php`; admin and guardian views will render front/back documents through their existing authorized routes, while relationship evidence will use the existing inline document endpoint. No schema, authorization, or storage-disk changes are required.

**Tech Stack:** Laravel, PHPUnit feature tests, Blade, Alpine.js, Tailwind CSS.

## Global Constraints

- Execute directly in the current `main` worktree; do not create or use a separate worktree.
- Preserve unrelated working-tree edits and generated assets.
- Keep private guardian and relationship documents behind the existing authorized controller routes.
- Use “Dependent Account Verification” for the dependent review UI; do not change internal legacy route/method names in this refinement.

---

### Task 1: Require back images for National ID and Passport

**Files:**
- Modify: `config/guardian_identity.php:4-15`
- Test: `tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php`

**Interfaces:**
- Consumes: `GuardianIdentityVerificationController::create()` and `StoreGuardianIdentityVerificationRequest::rules()` configuration.
- Produces: `idTypeRequirements[national_id].requires_back === true` and `idTypeRequirements[passport].requires_back === true` for the existing Alpine form and request validation.

- [x] **Step 1: Write the failing test**

Add a form contract test that requests the guardian verification page and asserts that the rendered requirement payload marks both requested ID types as requiring a back image. Add a validation test that omitting `government_id_back` for each type returns a validation error.

- [x] **Step 2: Run the focused tests to verify the new expectation fails**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php`

Expected: the new National ID/Passport back-image assertions fail because both config entries currently have `requires_back` set to `false`.

- [x] **Step 3: Write the minimal implementation**

Change only the two config entries:

```php
'national_id' => ['label' => 'National ID (PhilSys)', 'requires_back' => true],
'passport' => ['label' => 'Passport', 'requires_back' => true],
```

- [x] **Step 4: Run the focused tests to verify the upload contract passes**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php`

Expected: PASS, including front/back storage and missing-back validation coverage.

- [x] **Step 5: Commit**

```powershell
git add config/guardian_identity.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php
git commit -m "fix: require two-sided guardian ids"
```

### Task 2: Refine the dependent verification dashboard review modal

**Files:**
- Modify: `resources/views/admin/parent-verifications/index.blade.php:772-1285`
- Test: `tests/Feature/Admin/AdminParentChildVerificationUiTest.php`

**Interfaces:**
- Consumes: `ParentChildVerificationController::index()`, the existing `parents.document` route, and the child moderation endpoints.
- Produces: a dependent row with only a view/review button; a modal titled “Dependent Account Verification”; and front/back guardian ID cards using authorized URLs.

- [x] **Step 1: Write the failing test**

Add a child-table response assertion that checks for the two guardian document route URLs, “Dependent Account Verification”, and “Guardian Government ID - Front/Back”. Assert that the child-table slice contains no child archive/delete controls or child archive/delete route names, while the review and moderation controls remain present.

- [x] **Step 2: Run the focused admin UI test to verify the expectation fails**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/AdminParentChildVerificationUiTest.php`

Expected: the new assertions fail because the child slice currently uses public-storage URLs, renders only one guardian ID, labels the modal “Child Verification”, and includes archive/delete controls.

- [x] **Step 3: Write the minimal implementation**

In the child row Blade block:

```php
$guardianDocumentPaths = [
    'front' => (string) ($application->parent?->parent_id_document_path ?? ''),
    'back' => (string) ($application->parent?->parent_id_document_back_path ?? ''),
];
$guardianIdentityDocuments = collect($guardianDocumentPaths)->map(
    fn (string $path, string $side): array => [
        'side' => $side,
        'label' => 'Guardian Government ID - '.ucfirst($side),
        'url' => $path === '' || ! $application->parent
            ? null
            : route('admin.parent-verifications.parents.document', [$application->parent, $side]),
        'is_pdf' => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf',
    ],
)->values();
```

Render the collection as two cards with image/iframe/file fallback and download links. Replace visible dependent “Child Verification” copy with “Dependent Account Verification” and “Dependent Account”. Remove only the child row’s `actionConfirmOpen`, archive/delete buttons, confirmation modal, hidden forms, and their unused Alpine methods; leave approve/reject moderation actions intact.

- [x] **Step 4: Run the focused admin UI test to verify the dashboard refinement passes**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/AdminParentChildVerificationUiTest.php`

Expected: PASS, including existing relationship-review assertions and the new dependent queue assertions.

- [x] **Step 5: Commit**

```powershell
git add resources/views/admin/parent-verifications/index.blade.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php
git commit -m "refine dependent verification review"
```

### Task 3: Add previews to guardian relationship resubmission evidence

**Files:**
- Modify: `resources/views/parent/relationship-verifications/show.blade.php:76-99`
- Test: `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php`

**Interfaces:**
- Consumes: `GuardianRelationshipVerificationController::show()` document relation and `GuardianRelationshipVerificationController::document()` with `?inline=1`.
- Produces: submitted evidence cards with image previews, PDF iframe previews, accessible fallback text, and retained download links.

- [x] **Step 1: Write the failing test**

Add a submitted-evidence view test with a stored image document and assert that the response contains its inline document URL, an image preview with an accessible alt label, a “Preview” affordance, and a download link. Keep the existing document authorization/download test unchanged.

- [x] **Step 2: Run the focused evidence tests to verify the expectation fails**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php`

Expected: the new assertions fail because the page currently renders only a filename/type/side link.

- [x] **Step 3: Write the minimal implementation**

For each submitted document, derive an inline URL and MIME-based preview:

```php
$documentUrl = route('parent.relationship-verifications.documents.show', [$relationship, $document]);
$inlineDocumentUrl = $documentUrl.'?inline=1';
$isImage = str_starts_with((string) $document->mime_type, 'image/');
$isPdf = (string) $document->mime_type === 'application/pdf';
```

Render an image inside a constrained preview card when `$isImage`, an iframe when `$isPdf`, and a clear non-preview fallback otherwise. Keep metadata and a download link pointing to `$documentUrl`.

- [x] **Step 4: Run the focused evidence tests to verify the preview passes**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php`

Expected: PASS, including ownership, inline rendering, download, and submission behavior.

- [x] **Step 5: Commit**

```powershell
git add resources/views/parent/relationship-verifications/show.blade.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
git commit -m "feat: preview submitted relationship evidence"
```

### Task 4: Final verification and review

**Files:**
- Verify: `config/guardian_identity.php`, `resources/views/admin/parent-verifications/index.blade.php`, `resources/views/parent/relationship-verifications/show.blade.php`

- [x] **Step 1: Run all affected feature tests**

Run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php`

Expected: all tests pass.

- [x] **Step 2: Run PHP syntax checks**

Run: `php -l app/Http/Controllers/Admin/ParentChildVerificationController.php; php -l app/Http/Controllers/Auth/GuardianIdentityVerificationController.php; php -l app/Http/Requests/Auth/StoreGuardianIdentityVerificationRequest.php`

Expected: no syntax errors.

- [x] **Step 3: Inspect the final diff and repository status**

Run: `git diff --check; git status --short`

Expected: no whitespace errors; only intended source/test/plan commits are attributed to this implementation, while pre-existing user and generated-file changes remain untouched.

- [x] **Step 4: Complete a code review and report any limitation**

Review the changed Blade conditionals for private-route usage, accessibility labels, and preservation of the user’s existing uncommitted Blade edits. If browser tooling is available, open the affected admin and guardian pages for a visual smoke check; otherwise report that automated feature coverage and Blade syntax checks were used.
