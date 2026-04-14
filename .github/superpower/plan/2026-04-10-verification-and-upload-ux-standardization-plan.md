# Verification and Upload UX Standardization Implementation Plan

**Goal:** Standardize admin parent/child moderation UX and registration upload persistence with safer moderation actions, consistent terminology, and resilient multi-step file handling.

**Architecture:** Keep Laravel server-rendered Blade + Alpine. Add a reusable session-backed temp upload service in app/Services, keep controllers orchestration-only, and keep moderation + upload contracts additive.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, TinyMCE (existing bundled build), PHPUnit.

---

## Implementation Scope

- Admin Parent and Child verification queue/modal UX standardization
- Rejection/approval interaction safety updates
- Parent and Child registration upload preview + persistence
- My Children action cleanup

Out of scope:
- Role/permission model changes
- Verification policy changes beyond requested UX behavior
- Design system rewrite

---

## Affected Files

### Existing files
- routes/admin.php
- routes/auth.php
- app/Http/Controllers/Admin/ParentChildVerificationController.php
- app/Http/Controllers/Auth/ParentRegistrationController.php
- app/Http/Requests/Admin/RejectParentVerificationRequest.php
- app/Http/Requests/Admin/RejectChildVerificationRequest.php
- resources/views/admin/parent-verifications/index.blade.php
- resources/views/auth/parent-register.blade.php
- resources/views/auth/child/step3-credentials.blade.php
- resources/views/parent/children/index.blade.php

### New files
- app/Services/Auth/RegistrationTempUploadService.php
- app/Http/Requests/Auth/UploadParentTempDocumentRequest.php
- app/Http/Requests/Auth/UploadChildTempDocumentRequest.php
- app/Http/Requests/Auth/RemoveTempUploadRequest.php
- resources/views/admin/parent-verifications/partials/moderation-modal-shell.blade.php
- resources/views/admin/parent-verifications/partials/rejection-form-fields.blade.php
- tests/Feature/Admin/AdminParentChildVerificationUiTest.php
- tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
- tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php
- tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php
- tests/Unit/Services/RegistrationTempUploadServiceTest.php
- tests/Feature/Parent/ParentChildrenActionsUiTest.php

---

## Task Plan (TDD, Bite-Sized)

### Task 1: Parent/Child verification UI baseline test

**Step 1: Write failing UI test**
- File: `tests/Feature/Admin/AdminParentChildVerificationUiTest.php`
- Add tests that assert:
  - Parent modal title format: `Parent Verification - {User Name}`
  - Child modal title format: `Child Verification - {User Name}`
  - `Verification Details` exists
  - `Verification Transparency Details` does not exist
  - `Reviewed At` does not exist in queue details
  - `Document Type` does not exist in queue/details
  - `Parent Document Available` does not exist in child details

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationUiTest
  ```
- Expected output (example):
  ```text
  FAIL  Tests\Feature\Admin\AdminParentChildVerificationUiTest
  Failed asserting that ... contains "Parent Verification -"
  ```

**Step 3: Implement minimal UI changes**
- File: `resources/views/admin/parent-verifications/index.blade.php`
- Apply:
  - Remove reviewed/document-type/parent-doc-available metadata entries
  - Rename `Verification Transparency Details` to `Verification Details`
  - Update modal headers to required format
  - Keep action-first layout and move document preview into expandable block

**Step 4: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationUiTest
  ```
- Expected output:
  ```text
  PASS  Tests\Feature\Admin\AdminParentChildVerificationUiTest
  ```

---

### Task 2: Shared moderation modal shell extraction

**Step 1: Write failing assertion for shared structure markers**
- File: `tests/Feature/Admin/AdminParentChildVerificationUiTest.php`
- Add assertions for shared modal landmarks/classes used by both parent/child moderation.

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationUiTest
  ```

**Step 3: Implement shared partials**
- New file: `resources/views/admin/parent-verifications/partials/moderation-modal-shell.blade.php`
- New file: `resources/views/admin/parent-verifications/partials/rejection-form-fields.blade.php`
- Update: `resources/views/admin/parent-verifications/index.blade.php`
  - Replace duplicated parent/child modal fragments with shared partials

**Step 4: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationUiTest
  ```

---

### Task 3: Approval Review -> Confirm interaction flow

**Step 1: Write failing workflow test**
- File: `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`
- Add tests that verify:
  - pending parent/child approvals still succeed through existing approve endpoints
  - frontend state requires confirm action before submit (UI marker/assertion)
  - non-pending approval returns conflict as before

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationModerationWorkflowTest
  ```

**Step 3: Implement confirm modal behavior**
- File: `resources/views/admin/parent-verifications/index.blade.php`
- Apply:
  - Replace direct approve click with Review action opening confirm modal
  - Confirm modal message:
    - `Are you sure you want to approve this verification?`
  - Actions:
    - `Confirm`
    - `Cancel`
  - Keep request payload/route contracts unchanged

**Step 4: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationModerationWorkflowTest
  ```

---

### Task 4: Rejection `Other` requires TinyMCE rich text and remove warning checkbox

**Step 1: Write failing validation tests**
- File: `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`
- Add tests:
  - reject with `reason_code=others` and empty `custom_reason` fails
  - reject with `reason_code=others` and non-empty rich text passes

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationModerationWorkflowTest
  ```

**Step 3: Implement backend validation + compose behavior**
- Files:
  - `app/Http/Requests/Admin/RejectParentVerificationRequest.php`
  - `app/Http/Requests/Admin/RejectChildVerificationRequest.php`
  - `app/Http/Controllers/Admin/ParentChildVerificationController.php`
- Apply:
  - enforce meaningful custom content when `reason_code=others`
  - remove `issue_warning` dependency from composed rejection reason
  - preserve response payload keys and route contracts

**Step 4: Implement TinyMCE UI integration**
- File: `resources/views/admin/parent-verifications/index.blade.php`
- Apply:
  - Show TinyMCE editor when `reason_code === 'others'`
  - Use baseline config pattern from existing admin moderation TinyMCE setup
  - Remove `Issue warning to account holder` checkbox
  - Ensure editor init/destroy is lifecycle-safe in dynamic modal usage

**Step 5: Run tests and verify pass**
- Command:
  ```bash
  php artisan test --filter=AdminParentChildVerificationModerationWorkflowTest
  ```

---

### Task 5: Temp upload session service (shared model)

**Step 1: Write failing unit tests**
- File: `tests/Unit/Services/RegistrationTempUploadServiceTest.php`
- Add tests for:
  - store metadata by flow+step
  - get metadata for rehydration
  - replace deletes old temp file and updates metadata
  - remove deletes temp file and clears session key
  - finalize moves temp file to final location and clears session key

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=RegistrationTempUploadServiceTest
  ```

**Step 3: Implement service**
- New file: `app/Services/Auth/RegistrationTempUploadService.php`
- Methods:
  - `store(string $flow, string $step, UploadedFile $file): array`
  - `get(string $flow, string $step): ?array`
  - `remove(string $flow, string $step): void`
  - `finalize(string $flow, string $step, string $targetDir, string $targetPrefix): ?string`
- Session key model:
  - `registration_temp_uploads.{flow}.{step}`

**Step 4: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=RegistrationTempUploadServiceTest
  ```

---

### Task 6: Parent registration upload preview/persistence

**Step 1: Write failing feature tests**
- File: `tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php`
- Add tests for:
  - temp upload endpoint stores preview-ready metadata
  - parent registration step rehydrates preview on GET
  - remove endpoint clears session + temp file
  - submit step without new file uses existing temp metadata
  - back-navigation preserves uploaded preview

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=ParentRegistrationUploadPersistenceTest
  ```

**Step 3: Add form request(s) and routes**
- New files:
  - `app/Http/Requests/Auth/UploadParentTempDocumentRequest.php`
  - `app/Http/Requests/Auth/RemoveTempUploadRequest.php`
- Update: `routes/auth.php`
  - Add guest endpoints for parent temp upload create/remove

**Step 4: Implement controller flow**
- File: `app/Http/Controllers/Auth/ParentRegistrationController.php`
- Apply:
  - inject/use temp upload service
  - add endpoints for upload/remove
  - rehydrate preview state in `create()`
  - keep existing account creation contract, but finalize from temp service when account is created

**Step 5: Implement UI preview/remove/replace**
- File: `resources/views/auth/parent-register.blade.php`
- Apply:
  - immediate preview block after upload
  - remove (X) action
  - replace behavior by selecting a new file
  - if existing upload exists, show preview instead of duplicate-upload blocking message

**Step 6: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=ParentRegistrationUploadPersistenceTest
  ```

---

### Task 7: Child registration upload restriction + preview/persistence

**Step 1: Write failing feature tests**
- File: `tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php`
- Add tests for:
  - child temp upload accepts only PSA birth certificate allowed types (jpg/jpeg/png/pdf per current policy)
  - child credentials step rehydrates preview from session
  - remove/replace syncs session metadata
  - submit requires preview-ready temp state
  - finalize move stores final verification document path and clears temp session

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=ChildRegistrationUploadPersistenceTest
  ```

**Step 3: Add request and routes**
- New file: `app/Http/Requests/Auth/UploadChildTempDocumentRequest.php`
- Update: `routes/auth.php`
  - Add verified parent endpoints for child temp upload create/remove

**Step 4: Implement controller flow**
- File: `app/Http/Controllers/Auth/ParentRegistrationController.php`
- Apply:
  - in child credentials GET, include rehydrated preview metadata
  - in child credentials POST, use temp upload service data as source of truth
  - finalize temp upload to `child-verifications/{parent_id}` before persisting relation path

**Step 5: Implement child credentials UI**
- File: `resources/views/auth/child/step3-credentials.blade.php`
- Apply:
  - upload label text: PSA Birth Certificate only
  - immediate preview
  - remove/replace actions
  - prevent submit when preview-ready state is missing

**Step 6: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=ChildRegistrationUploadPersistenceTest
  ```

---

### Task 8: My Children action cleanup

**Step 1: Write failing UI test**
- File: `tests/Feature/Parent/ParentChildrenActionsUiTest.php`
- Add tests that assert:
  - duplicate approved-child actions are removed
  - one primary action remains for approved child card
  - pending/rejected state renders concise non-redundant action region

**Step 2: Run test and verify failure**
- Command:
  ```bash
  php artisan test --filter=ParentChildrenActionsUiTest
  ```

**Step 3: Implement cleanup**
- File: `resources/views/parent/children/index.blade.php`
- Apply:
  - remove duplicate links to same target route
  - keep single primary action and one contextual secondary state element

**Step 4: Run test and verify pass**
- Command:
  ```bash
  php artisan test --filter=ParentChildrenActionsUiTest
  ```

---

### Task 9: Final verification run

**Step 1: Run all new/updated targeted tests**
- Commands:
  ```bash
  php artisan test --filter=AdminParentChildVerificationUiTest
  php artisan test --filter=AdminParentChildVerificationModerationWorkflowTest
  php artisan test --filter=RegistrationTempUploadServiceTest
  php artisan test --filter=ParentRegistrationUploadPersistenceTest
  php artisan test --filter=ChildRegistrationUploadPersistenceTest
  php artisan test --filter=ParentChildrenActionsUiTest
  ```
- Expected output:
  ```text
  PASS ... (all listed test classes)
  ```

**Step 2: Run broader auth/admin smoke**
- Commands:
  ```bash
  php artisan test --testsuite=Feature --filter=Auth
  php artisan test --testsuite=Feature --filter=Admin
  ```
- Expected output:
  ```text
  PASS with no regressions in related flows
  ```

---

## Rollout Sequence

1. Ship service + requests + routes for temp upload API.
2. Ship admin moderation modal refactor + confirm flow + rejection rich text behavior.
3. Ship parent registration preview persistence.
4. Ship child registration preview persistence.
5. Ship My Children action cleanup.
6. Run targeted and smoke test packs.

---

## Risks and Mitigations

- TinyMCE init conflicts in dynamic modals:
  - Use explicit init/remove per open/close lifecycle and selector scoping.
- Session temp file staleness:
  - Always remove old file on replace and clear session on finalize/remove.
- Verification decision regressions:
  - Preserve existing moderation route contracts and payload keys.

---

## Execution Handoff

Next mode: superpower-execute

Execution rule: follow this plan task-by-task in strict TDD order (test -> fail -> implement -> pass), and report command outputs for each verification step.
