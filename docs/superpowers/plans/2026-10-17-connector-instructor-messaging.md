# Connector Instructor Messaging Implementation Plan

> **For agentic workers:** Use executing-plans to implement these tasks inline. Steps use checkbox syntax for tracking.

**Goal:** Allow Connector hosts and delegated educator managers to negotiate privately with instructors.

**Architecture:** Add a Connector conversation context to shared chat. Centralize eligible negotiation queries in `ConnectorConversationService`, reuse `ConnectorAccessService` for effective host permissions, and reuse shared chat rendering and message actions.

**Tech Stack:** Laravel, Eloquent/MySQL, Blade, Alpine, Vite, PHPUnit.

## Global Constraints

- Preserve existing checkout edits and development data; do not reset or reseed development.
- Use an incremental nullable Connector foreign key; negotiation participant one is organizer, participant two instructor.
- Seminar/webinar host permission implies educator management; no subscription/enrollment requirement.
- Only participants with current eligibility can access negotiation data or private attachments.
- Payment discussions do not change checkout prices or financial history.
- Run database tests serially using the configured isolated `cc_db_test` database.

### Task 1: Host access and negotiation lifecycle

Files: `app/Services/Connectors/ConnectorAccessService.php`, new `app/Services/Chat/ConnectorConversationService.php`, `app/Services/Chat/ChatAuthorizationService.php`, `app/Services/Chat/ChatService.php`, `app/Models/Conversation.php`, new migration `database/migrations/2026_10_17_000001_add_connector_id_to_conversations.php`, new `tests/Feature/Connectors/ConnectorInstructorMessagingTest.php`.

- [x] Write HTTP tests showing a member with only `connector.manage_seminars` can access Educators and start/reopen a private thread; ordinary members and unrelated users cannot.
- [x] Run `php artisan test tests/Feature/Connectors/ConnectorInstructorMessagingTest.php` and verify missing-feature failures.
- [x] Add effective permission keys, the nullable foreign key and conversation type, and a service with `instructors(): Builder`, `visibleTo(User $viewer): Builder`, and `start(User $organizer, Connector $connector, User $instructor): Conversation`.
- [x] Reuse the eligible query in conversation listing and authorization; reject generic negotiation creation. Verify both participants can send and permission/membership/status revocation disables history, sends, reads and channel access.

### Task 2: Directory, inbox and notifications

Files: new `app/Http/Controllers/Connector/EducatorController.php`, `app/Http/Controllers/Connector/DashboardController.php`, `routes/connector.php`, `routes/web.php`, `resources/views/layouts/connector-app.blade.php`, replace educator stub with `resources/views/connectors/educators/index.blade.php`, new `resources/views/connectors/educators/messages.blade.php`, shared chat views/store, `app/Http/Controllers/Chat/ConversationController.php`, chat notification/listener/events.

- [x] Add tests for searchable active instructors, Connector layout, names/context, separate threads per Connector and per organizer, notifications and denied cross-participant access.
- [x] Replace the educator placeholder with a paginated directory and CSRF-protected Message forms. Add a Messages navigation link and render shared chat with the Connector context.
- [x] Preserve global unread behavior while filtering Connector inbox conversations; hide learner discovery in that inbox. Route organizer notification links back to the Connector workspace and instructor links to existing chat.
- [x] Ensure broadcasts and notifications do not deliver negotiation contents after eligibility is revoked. Run the new feature test and existing chat regressions.

### Task 3: Private attachments and release evidence

Files: `app/Services/Chat/ChatService.php`, `app/Support/Chat/MessagePayloadFormatter.php`, new `app/Http/Controllers/Chat/MessageAttachmentController.php`, `routes/web.php`, `docs/connector-marketplace-and-verification-e2e-runbook.md`.

- [x] Test private file storage, participant-only downloads, denied removed/deleted message downloads and unchanged public learning-chat behavior.
- [x] Store negotiation attachments on `local`, return authorized download URLs and clean stored files on failed message writes. Recheck negotiation authorization on download; use private/no-store and nosniff headers.
- [x] Run focused Connector messaging and existing Chat tests serially. Run focused Pint, JS syntax, a separate-output Vite build and `git diff --check`.
- [x] Inspect the development schema, apply only the new forward migration, and verify existing conversation/message counts are preserved.
- [x] Add manual E2E steps and record executed checks separately from browser/staging release gates. Review the complete connected diff before handing off.
