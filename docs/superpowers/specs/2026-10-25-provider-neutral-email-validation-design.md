# Provider-Neutral Email Validation Design

**Date:** 2026-10-25

**Status:** Approved

**Scope:** Current branch only; no separate worktree, commit, push, or database migration

## Intent

Allow users to register and maintain accounts with any syntactically valid email address instead of requiring `@gmail.com`. Gmail, Yahoo, Outlook, educational domains, and temporary-email domains must be treated the same. Existing authentication, uniqueness, verification, password-reset, and guardian workflows remain in place.

## Goals

- Remove every active server-side Gmail-only validation rule.
- Validate email syntax without a provider allowlist, denylist, or live DNS lookup.
- Keep clearly malformed addresses invalid.
- Make active forms, verification guidance, FAQ, privacy, and terms copy match the provider-neutral behavior.
- Preserve the existing Gmail inbox shortcut for Gmail users without showing it to users of other providers.
- Add focused regressions for Gmail, common non-Gmail providers, educational domains, temporary-email domains, malformed input, duplicate addresses, admin updates, guardian registration, and login.

## Non-Goals

- No disposable-email blocking or domain reputation service.
- No MX, DNS, deliverability, or mailbox-existence checks.
- No SMTP configuration changes.
- No authentication, authorization, role, password-policy, or verification-flow redesign.
- No model, database constraint, schema, migration, or development-data changes.
- No change to guardian-created child email generation.
- No broad email-rule abstraction or unrelated refactor.
- No generated asset rebuild unless an unexpected source dependency requires it.
- No changes to Gmail SMTP documentation, the platform contact address, or historical plans.
- No connector-design implementation.

## Current State

Three backend paths enforce Gmail:

1. `app/Http/Requests/AccountInfoRequest.php` uses `email:rfc,dns`, `ends_with:@gmail.com`, and a Gmail-only error message for learner registration.
2. `app/Http/Controllers/Auth/ParentRegistrationController.php` applies the same rules inline for guardian registration.
3. `app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php` uses `email:rfc`, `ends_with:@gmail.com`, and a Gmail-only admin-login message.

General login, admin login, instructor login, password reset, ordinary profile updates, admin-managed users, connector invitations, connector organization email, billing email, models, migrations, and API routes are already provider-neutral. No JavaScript or HTML `pattern` enforces Gmail.

The active UI still contains Gmail-specific placeholders, guidance, inbox links, FAQ statements, privacy statements, and terms. Gmail SMTP documentation and the public contact address describe sending infrastructure or contact details rather than recipient eligibility and are outside this change.

## Approaches Considered

### A. Targeted provider-neutral update — selected

Remove the three Gmail rules, remove DNS validation from the two registration paths, update active user-facing policy copy, conditionally retain the Gmail inbox shortcut, and add focused regression coverage. This resolves the behavior and messaging mismatch without restructuring authentication.

### B. Backend-only removal

This is the smallest code change but leaves registration, verification, FAQ, privacy, and terms pages falsely claiming that Gmail is required. It was rejected because the visible product would contradict its validation behavior.

### C. Central shared email-policy abstraction

A reusable rule object could standardize all email fields, but most existing fields are already provider-neutral and have context-specific uniqueness or optionality rules. It was rejected as unnecessary scope and regression risk.

## Backend Design

Learner and guardian registration will use Laravel's `email:rfc` format validation. Their existing required, string, maximum-length, uniqueness, normalization, password, session, account-creation, notification, and verification behavior remains unchanged. Removing the `dns` modifier ensures a syntactically valid address is not rejected because of network availability, missing public DNS during validation, or provider type.

The admin credentials request will retain its current `email:rfc`, maximum-length, optionality, and uniqueness behavior while removing only the Gmail suffix rule and Gmail-specific message.

There will be no domain allowlist or denylist. An address such as `person@temporary-mail.com` will be evaluated by the same syntax rule as `person@gmail.com`.

## Guardian-Created Child Accounts

Gmail guardians will continue to receive the existing Gmail plus-alias suggestion and generated child address. Non-Gmail guardians will continue using the existing `username@child.sexed-platform.local` fallback. This behavior is not an email-provider restriction, and generalizing plus aliases would incorrectly assume every provider supports Gmail semantics.

## User Interface and Copy

Active email examples will use provider-neutral placeholders such as `name@example.com`. Gmail-only instructions will become general valid-email and verification-inbox guidance.

The verification views will derive presentation from the authenticated user's actual address:

- Gmail users continue seeing the existing external Gmail inbox shortcut.
- Non-Gmail users see provider-neutral inbox instructions and no misleading Gmail link.
- This conditional presentation does not validate, allow, or deny any domain.

FAQ, privacy, terms, guardian registration, password recovery, learner registration, and admin-profile wording will no longer state or imply that Gmail is required. The Gmail SMTP provider reference, Gmail setup guide, platform contact address, and historical planning documents remain unchanged.

## Validation and Error Behavior

- Valid RFC-formatted Gmail and non-Gmail addresses pass subject to existing uniqueness and length rules.
- Empty values, missing local parts, missing domains, doubled `@` characters, whitespace-damaged addresses, and other clearly malformed values fail.
- Duplicate addresses continue using the existing duplicate-email messages.
- The generic learner-registration invalid-email message remains `Please enter a valid email address.`
- Verification remains required where it is currently required; accepting a domain does not bypass email ownership verification.

## Test Strategy

Focused tests will cover:

- Gmail remains accepted.
- Yahoo and Outlook are accepted.
- `person@school.edu.ph` is accepted.
- `person@temporary-mail.com` is accepted without provider-specific rejection.
- Clearly malformed addresses are rejected.
- Duplicate email protection is unchanged.
- Guardian registration accepts a non-Gmail address.
- Admin credential updates accept non-Gmail addresses and still reject invalid syntax.
- A non-Gmail account can authenticate through the existing login flow.
- Gmail verification pages retain the Gmail shortcut while non-Gmail pages omit it.
- Active rendered copy no longer states that Gmail is required.

Database-backed feature tests extend `Tests\TestCase`, which globally uses `RefreshDatabase` and seeds permissions. Before any database-backed test is run, the effective environment, connection, and database name must be checked. Tests may proceed only when the environment is `testing` and the effective database is the isolated `cc_db_test`. The database-free copy test extends PHPUnit's base test case instead. The development database must never be reset or treated as disposable.

## Acceptance Criteria

1. A valid Gmail address still registers and logs in.
2. Valid Yahoo, Outlook, and educational-domain addresses are accepted.
3. A syntactically valid temporary-email domain is not rejected because of its provider.
4. Clearly invalid email formats remain rejected.
5. Duplicate, password, verification, authorization, and account-state behavior remains unchanged.
6. Active product copy does not claim Gmail is required.
7. Gmail-only inbox links are not shown to non-Gmail users.
8. No unrelated source, generated asset, database, or existing dirty-worktree changes are altered.

## Delivery Constraints

Implementation occurs in the current `feature/help-center-feedback-testimonials` checkout so it can later be integrated into `main`. Existing unrelated modifications must be preserved. Changes will be scoped explicitly; no `git add .`, automatic commit, push, branch switch, stash, separate worktree, destructive database command, or development-database test is authorized.
