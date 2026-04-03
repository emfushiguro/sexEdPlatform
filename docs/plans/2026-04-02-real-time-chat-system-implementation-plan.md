# Real-Time Chat System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement a secure, real-time, conversation-based chat system for admin, instructor, and learner roles with context chat support and hybrid request-gated learner-to-instructor initiation.

**Architecture:** Build a service-first chat domain with strict backend authorization and lineage validation, then deliver Reverb-powered private-channel broadcasting and Alpine/Echo live UI updates. Use request-gated instructor messaging for non-enrolled learners and keep moderation features staged for Phase 2.

**Tech Stack:** Laravel 12, PHP 8.2, MySQL, Blade, Tailwind CSS, Alpine.js, Laravel Echo, Laravel Reverb, PHPUnit Feature/Unit tests.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Enable Broadcasting and Reverb Baseline

**Files:**
- Create (if missing): `config/broadcasting.php`
- Create (if missing): `config/reverb.php`
- Create: `routes/channels.php`
- Modify: `bootstrap/app.php`
- Modify: `composer.json`
- Modify: `package.json`
- Modify: `resources/js/bootstrap.js`
- Create: `resources/js/echo.js`

**Step 1: Write the failing test**

Create: `tests/Feature/Chat/RealtimeBootstrapTest.php`

Test assertions:
1. `routes/channels.php` loads without runtime error.
2. broadcasting config contains `reverb` connection.
3. app bootstrap includes channel routing.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RealtimeBootstrapTest`
Expected: FAIL due to missing broadcasting/reverb bootstrap artifacts.

**Step 3: Write minimal implementation**

1. Install server dependency and frontend dependencies as needed.
2. Add/verify Reverb and Echo wiring.
3. Load `echo.js` from app entry path used by the project.
4. Ensure no existing app behavior regresses.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RealtimeBootstrapTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add composer.json package.json bootstrap/app.php config/broadcasting.php config/reverb.php routes/channels.php resources/js/bootstrap.js resources/js/echo.js tests/Feature/Chat/RealtimeBootstrapTest.php
git commit -m "feat: bootstrap laravel reverb and echo baseline"
```

## Task 2: Create Chat Domain Schema (Conversations and Messages)

**Files:**
- Create: `database/migrations/2026_04_02_120000_create_conversations_table.php`
- Create: `database/migrations/2026_04_02_120100_create_messages_table.php`
- Create: `app/Models/Conversation.php`
- Create: `app/Models/Message.php`
- Modify: `app/Models/User.php`

**Step 1: Write the failing test**

Create: `tests/Feature/Chat/ChatSchemaCoreTest.php`

Test assertions:
1. `conversations` and `messages` tables exist.
2. Required columns and foreign keys exist.
3. user relationships resolve for sent and received conversations/messages.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChatSchemaCoreTest`
Expected: FAIL because schema/models are absent.

**Step 3: Write minimal implementation**

1. Add migrations with required fields from approved design.
2. Add model casts, relationships, and helper scopes.
3. Add `User` relations for chat participation and sent messages.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ChatSchemaCoreTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add database/migrations/2026_04_02_120000_create_conversations_table.php database/migrations/2026_04_02_120100_create_messages_table.php app/Models/Conversation.php app/Models/Message.php app/Models/User.php tests/Feature/Chat/ChatSchemaCoreTest.php
git commit -m "feat: add core chat conversations and messages schema"
```

## Task 3: Add Request-Gating and Read-State Schema

**Files:**
- Create: `database/migrations/2026_04_02_120200_create_message_requests_table.php`
- Create: `database/migrations/2026_04_02_120300_create_conversation_reads_table.php`
- Create: `app/Models/MessageRequest.php`
- Create: `app/Models/ConversationRead.php`
- Modify: `app/Models/User.php`

**Step 1: Write the failing test**

Create: `tests/Feature/Chat/ChatSchemaRequestsAndReadsTest.php`

Test assertions:
1. `message_requests` and `conversation_reads` tables exist.
2. uniqueness for one read row per user per conversation.
3. request status transitions are representable.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChatSchemaRequestsAndReadsTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add request and read-state migrations.
2. Add model relationships and casts.
3. Add relation helpers on `User`.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ChatSchemaRequestsAndReadsTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add database/migrations/2026_04_02_120200_create_message_requests_table.php database/migrations/2026_04_02_120300_create_conversation_reads_table.php app/Models/MessageRequest.php app/Models/ConversationRead.php app/Models/User.php tests/Feature/Chat/ChatSchemaRequestsAndReadsTest.php
git commit -m "feat: add message request and conversation read state schema"
```

## Task 4: Implement Chat Authorization and Context Validation Services

**Files:**
- Create: `app/Services/Chat/ChatAuthorizationService.php`
- Create: `app/Services/Chat/ChatContextResolver.php`
- Modify: `app/Models/Conversation.php`
- Test: `tests/Unit/Chat/ChatAuthorizationServiceTest.php`
- Test: `tests/Unit/Chat/ChatContextResolverTest.php`

**Step 1: Write the failing tests**

Add unit tests for:
1. role pair matrix decisions.
2. enrolled and non-enrolled learner-to-instructor behavior.
3. context lineage checks for module/lesson/quiz.

**Step 2: Run tests to verify failure**

Run: `php artisan test --filter=ChatAuthorizationServiceTest`
Run: `php artisan test --filter=ChatContextResolverTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Centralize all create/send/subscribe decision logic.
2. Add helper methods for pair-key normalization and conversation type validation.
3. Add strict context lineage verification.

**Step 4: Run tests to verify pass**

Run: `php artisan test --filter=ChatAuthorizationServiceTest`
Run: `php artisan test --filter=ChatContextResolverTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Chat/ChatAuthorizationService.php app/Services/Chat/ChatContextResolver.php app/Models/Conversation.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Unit/Chat/ChatContextResolverTest.php
git commit -m "feat: add chat authorization matrix and context lineage validator"
```

## Task 5: Implement ChatService Orchestration

**Files:**
- Create: `app/Services/Chat/ChatService.php`
- Modify: `app/Models/Conversation.php`
- Modify: `app/Models/Message.php`
- Test: `tests/Unit/Chat/ChatServiceTest.php`

**Step 1: Write the failing test**

Test service methods:
1. `createOrGetConversation` uniqueness behavior.
2. `createMessageRequest` for non-enrolled learner -> instructor.
3. `sendMessage` persistence and `last_message_at` update.
4. `markConversationRead` read-state updates.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatServiceTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Use transaction-safe create/get patterns.
2. Route learner non-enrolled initiation to request path.
3. Keep logic side-effect-safe and idempotent where required.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatServiceTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Chat/ChatService.php app/Models/Conversation.php app/Models/Message.php tests/Unit/Chat/ChatServiceTest.php
git commit -m "feat: implement chat service orchestration and read-state updates"
```

## Task 6: Add Broadcast Events and Channel Authorization

**Files:**
- Create: `app/Events/Chat/MessageSent.php`
- Create: `app/Events/Chat/MessageRequestCreated.php`
- Create: `app/Events/Chat/MessageRequestResolved.php`
- Modify: `routes/channels.php`
- Test: `tests/Feature/Chat/ChatChannelAuthorizationTest.php`

**Step 1: Write the failing test**

Test assertions:
1. participants can subscribe to conversation private channel.
2. non-participants are denied.
3. request channels are only visible to request owner and target instructor.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatChannelAuthorizationTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add events implementing `ShouldBroadcast` with lean payloads.
2. Add channel callbacks that delegate to authorization service.
3. Deny by default when relationship checks fail.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatChannelAuthorizationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Events/Chat/MessageSent.php app/Events/Chat/MessageRequestCreated.php app/Events/Chat/MessageRequestResolved.php routes/channels.php tests/Feature/Chat/ChatChannelAuthorizationTest.php
git commit -m "feat: add chat events and private channel authorization"
```

## Task 7: Create Form Requests and Chat Controllers

**Files:**
- Create: `app/Http/Requests/Chat/StartConversationRequest.php`
- Create: `app/Http/Requests/Chat/SendMessageRequest.php`
- Create: `app/Http/Requests/Chat/ResolveMessageRequestRequest.php`
- Create: `app/Http/Controllers/Chat/ConversationController.php`
- Create: `app/Http/Controllers/Chat/MessageController.php`
- Create: `app/Http/Controllers/Chat/MessageRequestController.php`
- Modify: `routes/web.php`
- Modify: `routes/admin.php`
- Modify: `routes/instructor.php`
- Test: `tests/Feature/Chat/ChatHttpFlowTest.php`

**Step 1: Write the failing test**

Feature tests for:
1. list conversations.
2. start direct/context conversation.
3. send message.
4. accept/decline request.
5. forbidden paths return 403.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatHttpFlowTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Keep controllers thin and delegate orchestration to services.
2. Add role-protected routes in existing role route files.
3. Return clean JSON payloads for Alpine consumption.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatHttpFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Requests/Chat/StartConversationRequest.php app/Http/Requests/Chat/SendMessageRequest.php app/Http/Requests/Chat/ResolveMessageRequestRequest.php app/Http/Controllers/Chat/ConversationController.php app/Http/Controllers/Chat/MessageController.php app/Http/Controllers/Chat/MessageRequestController.php routes/web.php routes/admin.php routes/instructor.php tests/Feature/Chat/ChatHttpFlowTest.php
git commit -m "feat: add chat controllers form requests and role-based routes"
```

## Task 8: Build Shared Chat Blade Components and Role Pages

**Files:**
- Create: `resources/views/chat/index.blade.php`
- Create: `resources/views/chat/partials/conversation-list.blade.php`
- Create: `resources/views/chat/partials/conversation-panel.blade.php`
- Create: `resources/views/chat/partials/request-list.blade.php`
- Modify: `resources/views/admin/messages/index.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/layouts/instructor-app.blade.php`
- Modify: `resources/views/layouts/learner-sidebar.blade.php`
- Test: `tests/Feature/Chat/ChatPageRenderTest.php`

**Step 1: Write the failing test**

Assertions:
1. admin/instructor/learner can open chat page.
2. chat page renders role-aware layout and required sections.
3. old static preview is replaced by data-driven container.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatPageRenderTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Add a shared chat view and partials.
2. Keep TailAdmin style language already used in the project.
3. Add nav entry points in role layouts.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatPageRenderTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/chat/index.blade.php resources/views/chat/partials/conversation-list.blade.php resources/views/chat/partials/conversation-panel.blade.php resources/views/chat/partials/request-list.blade.php resources/views/admin/messages/index.blade.php resources/views/layouts/admin.blade.php resources/views/layouts/instructor-app.blade.php resources/views/layouts/learner-sidebar.blade.php tests/Feature/Chat/ChatPageRenderTest.php
git commit -m "feat: add shared role-aware chat pages and layout entry points"
```

## Task 9: Add Alpine Chat Store and Echo Listeners

**Files:**
- Create: `resources/js/chat/store.js`
- Modify: `resources/js/app.js`
- Modify: `resources/js/echo.js`
- Test: `tests/Feature/Chat/ChatRealtimeUiContractTest.php`

**Step 1: Write the failing test**

Contract-focused test assertions:
1. page bootstrap includes chat store script.
2. API payload shapes satisfy frontend contract.
3. broadcast event payload fields required by UI exist.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatRealtimeUiContractTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. Build Alpine store state for conversations/messages/unread/requests.
2. Wire Echo subscriptions for conversation and request channels.
3. Implement optimistic send and server-ack reconciliation.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatRealtimeUiContractTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/js/chat/store.js resources/js/app.js resources/js/echo.js tests/Feature/Chat/ChatRealtimeUiContractTest.php
git commit -m "feat: wire alpine chat store with echo realtime listeners"
```

## Task 10: Implement Read State and Unread Counters

**Files:**
- Modify: `app/Services/Chat/ChatService.php`
- Modify: `app/Http/Controllers/Chat/ConversationController.php`
- Modify: `resources/js/chat/store.js`
- Test: `tests/Feature/Chat/ChatUnreadAndReadStateTest.php`

**Step 1: Write the failing test**

Assertions:
1. unread count increments for receiver on send.
2. mark-read endpoint updates read state.
3. unread count clears for conversation after read.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatUnreadAndReadStateTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. calculate unread from `conversation_reads` and latest message ids.
2. add mark-read API and client call when opening/focusing thread.
3. update list counters in UI state.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatUnreadAndReadStateTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Chat/ChatService.php app/Http/Controllers/Chat/ConversationController.php resources/js/chat/store.js tests/Feature/Chat/ChatUnreadAndReadStateTest.php
git commit -m "feat: add unread counter and conversation read-state handling"
```

## Task 11: Add Reconnect Backfill and Failure Recovery

**Files:**
- Modify: `app/Http/Controllers/Chat/MessageController.php`
- Modify: `routes/web.php`
- Modify: `resources/js/chat/store.js`
- Test: `tests/Feature/Chat/ChatReconnectBackfillTest.php`

**Step 1: Write the failing test**

Assertions:
1. `messages/since/{lastMessageId}` backfill endpoint returns only newer messages.
2. failed optimistic message can be retried via API.
3. authorization applies to backfill endpoint.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatReconnectBackfillTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. add backfill endpoint and service method.
2. add retry behavior in Alpine store.
3. avoid duplicate insertion on reconnect sync.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatReconnectBackfillTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Chat/MessageController.php routes/web.php resources/js/chat/store.js tests/Feature/Chat/ChatReconnectBackfillTest.php
git commit -m "feat: add reconnect backfill and optimistic send recovery"
```

## Task 12: Add Browser Notification Opt-In and Badge Integration

**Files:**
- Modify: `resources/js/chat/store.js`
- Modify: `resources/views/chat/index.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/layouts/instructor-header.blade.php`
- Modify: `resources/views/layouts/learner-header.blade.php`
- Test: `tests/Feature/Chat/ChatNotificationBadgeTest.php`

**Step 1: Write the failing test**

Assertions:
1. unread badge values are rendered in role headers.
2. notification preference state is toggled and persisted.
3. active focused thread suppresses browser notification popup.

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ChatNotificationBadgeTest`
Expected: FAIL.

**Step 3: Write minimal implementation**

1. wire global unread badge update hook from chat store.
2. add optional browser notification prompt flow.
3. suppress notifications for active visible conversation.

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ChatNotificationBadgeTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/js/chat/store.js resources/views/chat/index.blade.php resources/views/layouts/admin.blade.php resources/views/layouts/instructor-header.blade.php resources/views/layouts/learner-header.blade.php tests/Feature/Chat/ChatNotificationBadgeTest.php
git commit -m "feat: add unread badge integration and optional browser notifications"
```

## Task 13: End-to-End Authorization and Regression Suite

**Files:**
- Create: `tests/Feature/Chat/ChatRolePermissionMatrixTest.php`
- Create: `tests/Feature/Chat/ChatRequestGateFlowTest.php`
- Create: `tests/Feature/Chat/ChatContextConversationFlowTest.php`
- Modify: `phpunit.xml` (if grouping/filtering is needed)

**Step 1: Write the failing tests**

Cover all locked decisions:
1. allowed and denied role combinations.
2. non-enrolled learner -> instructor request gate.
3. enrolled learner -> instructor direct activation.
4. module, lesson, quiz context start and send flows.

**Step 2: Run tests to verify failure**

Run: `php artisan test --filter=ChatRolePermissionMatrixTest`
Run: `php artisan test --filter=ChatRequestGateFlowTest`
Run: `php artisan test --filter=ChatContextConversationFlowTest`
Expected: initial FAIL until all edge cases are complete.

**Step 3: Write minimal implementation adjustments**

1. patch remaining auth and lineage edge cases.
2. patch payload and read-state edge cases.
3. confirm channel auth parity with HTTP auth.

**Step 4: Run tests to verify pass**

Run:
`php artisan test --filter=ChatRolePermissionMatrixTest`
`php artisan test --filter=ChatRequestGateFlowTest`
`php artisan test --filter=ChatContextConversationFlowTest`
`php artisan test --testsuite=Feature`
Expected: PASS for chat suites and no regressions in touched flows.

**Step 5: Commit**

```bash
git add tests/Feature/Chat/ChatRolePermissionMatrixTest.php tests/Feature/Chat/ChatRequestGateFlowTest.php tests/Feature/Chat/ChatContextConversationFlowTest.php phpunit.xml
git commit -m "test: add comprehensive chat permission and context regression suites"
```

## Task 14: Documentation and Operational Runbook

**Files:**
- Create: `docs/CHAT_REALTIME_SETUP_GUIDE.md`
- Modify: `.env.example`
- Modify: `README.md` (if this repo uses root run instructions)

**Step 1: Write the failing test/checklist**

Create a manual verification checklist entry in doc:
1. reverb server start command.
2. echo client env variables.
3. private channel auth verification steps.

**Step 2: Run validation to verify gap**

Run: `rg "REVERB|BROADCAST" .env.example README.md docs`
Expected: missing or incomplete runbook details.

**Step 3: Write minimal implementation**

1. document local/staging production setup.
2. document troubleshooting for websocket disconnection and fallback mode.
3. add environment keys and defaults.

**Step 4: Run validation to verify completion**

Run: `rg "REVERB|BROADCAST|chat.conversation" .env.example README.md docs/CHAT_REALTIME_SETUP_GUIDE.md`
Expected: all required ops references present.

**Step 5: Commit**

```bash
git add docs/CHAT_REALTIME_SETUP_GUIDE.md .env.example README.md
git commit -m "docs: add realtime chat setup and operations guide"
```

---

## Final Verification Pass (After Task 14)

Run:
1. `php artisan test --filter=Chat`
2. `php artisan test`
3. `npm run build`

Expected:
1. Chat test suites pass.
2. No regression in existing feature suites.
3. Frontend build succeeds with Echo integration.

## Rollout Notes

1. Deploy database migrations before enabling route access.
2. Ensure websocket process supervisor is active in target environment.
3. Enable feature flags (if introduced) gradually by role.
4. Monitor logs for channel auth denials and message send failures during first release window.

## Phase 2 Pointer (Not in this implementation pass)

After Phase 1 stabilization, start moderation workstream:
1. message reporting endpoints and UI,
2. admin moderation queue,
3. admin delete and temporary chat restrictions,
4. moderation audit and monitoring dashboard.
