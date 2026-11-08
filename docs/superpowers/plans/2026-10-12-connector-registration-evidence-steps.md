# Connector Registration Evidence Steps Implementation Plan

> **For agentic workers:** Use test-driven development in the current branch. Preserve unrelated working-tree changes and development data.

**Goal:** Show only selected-type evidence, place representative identity and authority together before confirmation, and align attachment controls with the platform's existing upload language.

**Architecture:** Keep the server registry and evidence validation authoritative. Derive visible requirement keys from the selected organization type in the Blade/Alpine stepper; reuse one evidence-card partial in the organization and representative steps. Keep the existing upload field names and private storage workflow.

**Tech Stack:** Laravel Blade, Alpine.js, Tailwind CSS, PHPUnit.

## Global constraints

- Use the current branch and preserve unrelated edits.
- Do not reset, wipe, or destructively reseed any database.
- Preserve existing requirement keys and evidence submission semantics.

### Task 1: Evidence selection and six-step layout

**Files:** `resources/views/connectors/register.blade.php`; `tests/Feature/Connectors/ConnectorRegistrationStepperTest.php`.

- [x] Write tests that expect six steps; Step 4 contains only organization requirements, Step 5 contains both representative requirements and reuse, and Step 6 contains confirmation.
- [x] Write a test that expects visibility to derive from the selected type and the school requirement keys when school is restored after validation.
- [x] Run `C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/Connectors/ConnectorRegistrationStepperTest.php --no-coverage --do-not-cache-result` and confirm the new tests fail.
- [x] Change Step 4 visibility to derive from the selected type; hide empty groups and disable hidden fields. Move representative evidence to Step 5 and confirmation to Step 6. Route field errors to their owning step.
- [x] Run the same focused PHPUnit command and confirm it passes.

### Task 2: Attachment affordance and documentation

**Files:** `resources/views/connectors/partials/evidence-card.blade.php`; `resources/views/connectors/register.blade.php`; `tests/Feature/Connectors/ConnectorRegistrationStepperTest.php`; `docs/connector-verification-end-to-end-test-runbook.md`.

- [x] Add a failing form-render test for an accessible branded file-picker area, selected-file feedback and a clear action while preserving `evidence[KEY][files][]` names.
- [x] Run the focused PHPUnit command and confirm the new assertion fails.
- [x] Extract the repeated evidence card to a Blade partial and use the platform's dashed brand attachment treatment. Add a small Alpine handler for selection, drag/drop, clearing, and type-switch cleanup.
- [x] Update the runbook's six-step and type-specific evidence checks.
- [x] Run focused registration and verification tests, Pint, `php artisan view:cache`, and `git diff --check`; report browser testing separately if unavailable.
- [x] Review all configured rule groups, render the non-representative groups in Step 4, and test activity-specific and optional regulatory cards; return focus to the file picker after clearing files.
