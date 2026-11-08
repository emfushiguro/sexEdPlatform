# Unrestricted Provider-Neutral Email Registration Design

**Status:** Approved for implementation

## Intent

Accept any syntactically valid email provider, including temporary/disposable providers, while preserving email ownership verification, literal uniqueness, password recovery, and abuse controls.

## Decisions

- Use Laravel's `email:rfc` validation only for account emails.
- Do not install ERAG or use disposable-domain lists, DNS/MX checks, mailbox probing, or validation APIs.
- Normalize account emails by trimming and lowercasing before validation, lookup, write, and uniqueness checks.
- Treat normalized literal values as identities; Gmail dots, plus-tags, and `googlemail.com` are not canonicalized.
- Keep the existing `users.email` unique index and soft-delete reservation behavior.
- Reverify human-entered email changes; require the current password for self-service email changes.
- Preserve guardian-generated child accounts as the explicit guardian-attested verification exception.
- Require verified email for protected learner, guardian, connector, instructor, and admin actions.
- Add native registration, resend, reset, and email-keyed rate limits using privacy-safe keyed fingerprints.
- Return generic password-reset responses and do not log raw addresses, tokens, or exception messages.

## Security boundary

Accepting a disposable address proves only that the user can receive mail at that moment. It does not prove identity, permanence, or good behavior. Verification, velocity limits, protected-feature gates, and behavioral controls remain the security boundary.

## Data safety

Inspect the existing schema, collation, unique index, and normalized collisions before implementation. If legacy data is incompatible, stop for remediation; do not backfill or reset development data as part of this feature.

## Compatibility

Implementation targets the actual checkout: Laravel 12.44, PHP 8.2, MySQL, Blade, Tailwind, Alpine.js, and PHPUnit. No Composer dependency is added.
