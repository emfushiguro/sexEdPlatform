# Connector instructor messaging QA — 2026-10-17

Implemented private organizer/instructor negotiation threads using shared chat. Seminar/webinar hosts (`connector.manage_seminars`) receive educator-management access by default. Delegated `connector.manage_educators` access remains available. Active membership and a verified Connector are required; negotiations do not require subscriptions or learning enrollment.

## Implemented scope

- Searchable, paginated active-instructor directory with a CSRF-protected Message action and separate Connector Messages navigation.
- Private threads scoped to Connector, organizer and instructor. Participant one is the organizer; participant two is the instructor. The context key includes the organizer identity so the two users can reverse responsibilities without reusing a revoked thread.
- Shared message UI, replies, notifications, unread counts, reporting and existing mutation limits. Organizers return to the Connector workspace from notification links; instructors use existing Chat.
- Current eligibility checks on conversation listing, history, sends, edits, deletes, reports, read receipts, private downloads and realtime channel authorization. Revoked/inactive/soft-deleted participants and suspended Connectors cannot use the thread. History is preserved.
- Private negotiation attachments with authenticated downloads, no-store/nosniff headers, and upload cleanup on failed message writes. Existing public learning-chat attachments retain their behavior.
- Global popup conversations remain globally loaded and restored; only the full-page Connector sidebar is filtered. The Connector view fetches an additional scoped page so older negotiation threads appear without displacing saved personal chats.
- No changes to marketplace pricing, checkout or financial history.

## Executed automated checks

| Check | Result |
| --- | --- |
| Messaging feature, existing Chat feature/unit, Connector seminar management and role reconciliation matrix | PASS: **129 tests, 826 assertions**, isolated `cc_db_test`, serial execution. |
| JavaScript messaging regressions | PASS: **5 tests**, including actual saved-popup restoration through global pagination, correct initial thread selection and revoked workspace fallback. |
| Scoped Pint | PASS: 16 messaging PHP files. |
| Additional PHP syntax checks | PASS: User model, Connector dashboard controller, web/Connector routes and Connector permission config. |
| JavaScript syntax | PASS: `resources/js/chat/store.js`. |
| Vite production build | PASS: 101 modules, output under `storage/framework/connector-messaging-build`. |
| Local compiled assets | Tested assets copied without deleting existing generated files; manifest references verified. Previous manifest retained at `storage/framework/connector-messaging-prebuild-manifest.json`. |
| Independent source review | Two concrete findings fixed and covered: saved popup preservation and reversed organizer roles. Follow-up review found no remaining concrete blocker. |
| Whitespace check | `git diff --check` passed; Git reported existing CRLF normalization notices in unrelated files. |

Command for the PHP matrix:

```powershell
php artisan test tests/Feature/Connectors/ConnectorInstructorMessagingTest.php tests/Feature/Chat tests/Unit/Chat tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php --compact --log-junit storage/framework/connector-messaging-regressions.xml
```

Other commands:

```powershell
node --test tests/JavaScript/connector-chat.test.mjs
node --check resources/js/chat/store.js
pnpm.cmd build --outDir storage/framework/connector-messaging-build --emptyOutDir
git diff --check
```

## Development migration and data preservation

Inspected the live `cc_db.conversations` schema before applying the migration: 15 existing columns, zero conversations and zero messages. Applied only `2026_10_17_000001_add_connector_id_to_conversations.php`; verified `connector_id` was added and both row counts remained zero. No other migrations or development resets/reseeding were run. Test resets used only the existing isolated `cc_db_test` infrastructure.

## Open release gates

- Interactive authenticated desktop/mobile and keyboard/screen-reader QA: **UNRUN**. Browser runtime discovery returned no connected browsers; no screenshot or interactive result is claimed.
- Live two-session realtime delivery and revocation with the deployed broadcasting/queue infrastructure: **UNRUN**. HTTP authorization and queued-event channel suppression are automated evidence only.
- Full PHPUnit suite, repository-wide Pint and staging backup/migration rehearsal: **UNRUN**.
- Payment negotiation is discussion only; there is no negotiated checkout price, agreement acceptance or contract-signing workflow.

The merged inbox approach makes two conversation-list requests per page in Connector Messages. Other chat pages retain one request per page. No additional dependency was introduced.

See [runbook section 13](connector-marketplace-and-verification-e2e-runbook.md#13-connector-organizer-and-instructor-messaging--2026-10-17) for the manual scenarios.
