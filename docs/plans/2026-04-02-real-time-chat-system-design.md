# Real-Time Chat System Design (Approach B: Hybrid Request-Gated)

**Date:** 2026-04-02  
**Status:** Approved  
**Approach:** Approach B (Hybrid Request-Gated Instructor Messaging)

## 1. Objective

Build a fully integrated, secure, real-time chat system for admin, instructor, and learner users with conversation-based threading and contextual learning support for module, lesson, and quiz discussions.

The system must:
1. Deliver real-time updates without page refresh.
2. Enforce role and enrollment boundaries from backend policy checks.
3. Support context-based educational discussions.
4. Follow existing TailAdmin-aligned UI patterns already used in the platform.
5. Stay extensible for moderation and advanced chat features in later phases.

## 2. Locked Decisions

1. Delivery model is phased (selected option d):
   - Phase 1: core chat and request-gated model.
   - Phase 2: moderation and safety tooling.
2. Conversation creation uses manual create plus auto-create fallback (option b).
3. Direct conversation uniqueness is one-per-pair and context uniqueness is one-per-pair-per-context (option a).
4. Context relationships must be lineage-valid (option b).
5. Authorization must run at create and every send (option b).
6. Learner -> instructor rule is hybrid:
   - If learner is enrolled in at least one module created by that instructor: direct chat opens.
   - If learner is not enrolled: a message request is created for instructor approval.
7. Instructor -> learner availability is learners enrolled in instructor-owned modules (option b).
8. Admin can message both learners and instructors, but no unrestricted read of non-admin conversations in Phase 1 (option a).
9. Real-time delivery uses optimistic UI plus server acknowledgment (option b).
10. Reconnect behavior uses auto-reconnect plus backfill by last known message id (option b).
11. Phase 1 supports text and safe links with sanitization (option b).
12. No user edit/delete in Phase 1 (option a).
13. Read state combines:
   - unread count per conversation,
   - conversation-level seen state (last_read_message_id / last_read_at).
14. Notifications include in-app badges and optional browser notifications (option c).
15. Phase 2 moderation minimum includes report, queue, admin delete, temporary restriction (option a).
16. Retention target is 1 year (option c).
17. Messages load with pagination (newest-first window, upward infinite scroll) (option b).
18. UI rollout includes dedicated inbox pages plus contextual entry points (option b).
19. Authorization belongs in service + policy/gate + channel auth callback (option b).
20. If websocket is unavailable, chat gracefully degrades to non-live refresh while preserving send/store (option b).

## 3. Scope

### 3.1 Phase 1 In Scope

1. Direct chat and context chat types:
   - direct
   - module_chat
   - lesson_chat
   - quiz_help
2. Request-gated learner -> instructor initiation for non-enrolled learners.
3. Role-aware and enrollment-aware authorization checks.
4. Private conversation channels through Reverb and Echo subscription/listening.
5. Conversation list UI, message stream UI, composer, unread badges, timestamps.
6. Reconnect and backfill handling.
7. Conversation search by participant and preview content.

### 3.2 Phase 2 In Scope

1. Report message flow.
2. Admin moderation review queue.
3. Admin message deletion tools.
4. Temporary chat restrictions.
5. Monitoring views for violations and moderation activity.

### 3.3 Out of Scope (Current)

1. Attachments and file uploads.
2. Emoji reactions.
3. Typing indicators.
4. AI-assisted replies.
5. Full text search over historical message body.

## 4. High-Level Architecture

```
Blade + Alpine.js UI
    -> HTTP Controller + Form Requests
    -> ChatService + ChatAuthorizationService + ContextResolver
    -> Database (conversations, messages, requests, read states)
    -> Broadcast Events (MessageSent, RequestCreated, RequestResolved)
    -> Laravel Reverb WebSocket Server
    -> Laravel Echo listeners in browser
    -> Alpine state updates (append/reorder/unread/autoscroll)
```

### 4.1 Responsibility Boundaries

1. Controllers: input validation, response shaping, no heavy business logic.
2. Services: policy checks, orchestration, persistence, and domain transitions.
3. Events: push only minimal payload needed for real-time UI updates.
4. Frontend state: rendering, optimistic UX, reconnect behavior.

## 5. Conversation and Lifecycle Model

### 5.1 Conversation Types

1. `direct`: one private thread between two users.
2. `module_chat`: thread tied to one module.
3. `lesson_chat`: thread tied to one lesson.
4. `quiz_help`: thread tied to one quiz.

### 5.2 Lifecycle States

1. `pending_request`: non-enrolled learner initiated instructor contact.
2. `active`: thread is open for normal two-way messaging.
3. `closed` (reserved for later): conversation disabled by policy/moderation.

### 5.3 Uniqueness Rules

1. Direct: one conversation per normalized participant pair.
2. Context: one conversation per normalized participant pair + context type + context target id.

## 6. Data Design

## 6.1 conversations table

Core fields:
1. `id`
2. `participant_one_id`
3. `participant_two_id`
4. `conversation_type` (`direct`, `module_chat`, `lesson_chat`, `quiz_help`)
5. `status` (`pending_request`, `active`, `closed`)
6. `module_id` nullable
7. `lesson_id` nullable
8. `quiz_id` nullable
9. `last_message_at`
10. `created_at`
11. `updated_at`

Recommended support fields:
1. `pair_key` (normalized pair string for robust uniqueness/indexing)
2. `context_key` (normalized context key)

## 6.2 messages table

Core fields:
1. `id`
2. `conversation_id`
3. `sender_id`
4. `message_body`
5. `created_at`
6. `updated_at`

Future-ready nullable fields:
1. `read_at`
2. `message_type`
3. `attachment_url`

## 6.3 message_requests table (Approach B requirement)

1. `id`
2. `requester_id`
3. `instructor_id`
4. `status` (`pending`, `accepted`, `declined`)
5. `initial_message`
6. `accepted_conversation_id` nullable
7. `decided_by_id` nullable
8. `decided_at` nullable
9. `created_at`
10. `updated_at`

## 6.4 conversation_reads table

1. `id`
2. `conversation_id`
3. `user_id`
4. `last_read_message_id` nullable
5. `last_read_at` nullable
6. `created_at`
7. `updated_at`

Unique key: (`conversation_id`, `user_id`).

## 6.5 Phase 2 moderation tables (planned)

1. `message_reports`
2. `moderation_actions`
3. `chat_restrictions`

## 7. Integrity and Constraint Rules

1. Direct thread must have all context ids as null.
2. `module_chat` must have module_id and null lesson_id/quiz_id.
3. `lesson_chat` must have lesson_id and lesson->module lineage must be valid.
4. `quiz_help` must have quiz_id and quiz->lesson/module lineage must be valid.
5. Sender must always be one of conversation participants.
6. Channel subscription must deny non-participants.

## 8. Authorization Matrix

### 8.1 Core Role Rules

1. Admin <-> instructor: allowed.
2. Admin <-> learner: allowed.
3. Instructor <-> learner: allowed with relationship checks.

### 8.2 Learner -> Instructor Initiation

1. If learner is approved-enrolled in any module created by instructor:
   - allow direct conversation activation.
2. Otherwise:
   - create message request in `pending` state.
   - instructor must accept before active direct messaging starts.

### 8.3 Send-Time Checks

On every send:
1. user is participant.
2. conversation state allows send.
3. no active restriction blocks sender.

## 9. Realtime and Event Design

### 9.1 Event Set (Phase 1)

1. `MessageSent`
   - message payload
   - sender summary
   - conversation id
   - timestamp
2. `MessageRequestCreated`
   - request id
   - requester summary
   - initial message preview
3. `MessageRequestResolved`
   - request id
   - resolution status
   - optional conversation id

### 9.2 Channel Pattern

1. `private-chat.conversation.{conversationId}`
2. `private-chat.requests.user.{userId}`

### 9.3 Delivery Semantics

1. UI appends optimistic message.
2. API returns authoritative message id.
3. Broadcast event syncs other subscribers.
4. reconnect triggers pull-backfill from last local message id.

## 10. API and Route Contract

### 10.1 Core Endpoints

1. `GET /chat/conversations`
2. `GET /chat/conversations/{conversation}/messages`
3. `POST /chat/conversations/start`
4. `POST /chat/conversations/{conversation}/messages`
5. `POST /chat/conversations/{conversation}/read`
6. `POST /chat/requests/{request}/accept`
7. `POST /chat/requests/{request}/decline`

### 10.2 Context Entry Endpoints

1. Start module chat.
2. Start lesson chat.
3. Start quiz help chat.

Each start action must apply context lineage validation and participant authorization.

## 11. Frontend UX Design

### 11.1 Shared Layout Contract

1. Left panel:
   - conversation search input
   - conversation rows with participant name, role badge, last preview, unread chip
2. Right panel:
   - conversation header with context badge
   - message list grouped chronologically
   - composer with send button

### 11.2 Behavior Contract

1. Auto-scroll when user is near bottom.
2. Preserve scroll when user is reading older messages.
3. Reorder conversation list by last_message_at.
4. Increment/decrement unread indicators from read-state events.

### 11.3 Request-Gated UX

1. Learner non-enrolled start shows request submission state.
2. Instructor inbox has pending request list/actions.
3. Accept converts request to active conversation.
4. Decline leaves audit state and informs requester.

## 12. Reliability and Failure Handling

1. Send failure marks optimistic message as failed with retry action.
2. Reconnect uses exponential backoff via Echo/Reverb default behavior.
3. Backfill endpoint fetches messages newer than last known id.
4. If authorization changes mid-session, composer is disabled and reason shown.
5. If websocket unavailable, HTTP send + periodic refresh still keep chat usable.

## 13. Security and Privacy

1. Private channels only.
2. Backend-first authorization (no trust in frontend role flags).
3. Input sanitization for message body and links.
4. Rate-limiting on send and request creation endpoints.
5. Audit logging for request accept/decline and moderation actions.

## 14. Moderation Design (Phase 2)

1. User can report a message with reason code and note.
2. Admin moderation queue shows pending reports.
3. Admin can:
   - delete message,
   - mark report resolved,
   - apply temporary chat restriction to user.
4. Restriction checks integrate into send authorization service.

## 15. Retention and Compliance

1. Keep chat and moderation records for 1 year.
2. Retention and purge policy executed by scheduled job.
3. Message deletion by moderation is soft-delete with audit preservation.
4. Access to moderation history is admin-only.

## 16. Testing Strategy

Required test suites:
1. Unit tests:
   - role/enrollment authorization matrix
   - context resolver lineage validation
   - request-gated transition logic
2. Feature tests:
   - conversation create/list/message/read endpoints
   - request accept/decline behavior
   - forbidden access attempts
3. Broadcast/channel tests:
   - participant channel authorization
   - non-participant denial
4. UI integration checks (server-rendered + Alpine behavior assertions where applicable).

## 17. Rollout Sequence

1. Add schema and indexes.
2. Build models and relationships.
3. Build authorization and service orchestration.
4. Add endpoints and controller actions.
5. Add events, channels, and Echo wiring.
6. Replace static message preview UI with live data-driven pages.
7. Validate with test suites and staging smoke checks.
8. Enable production websocket process and monitor.

## 18. Risks and Mitigations

1. Risk: duplicate conversations from race conditions.
   - Mitigation: unique keys + transaction retry logic.
2. Risk: stale permissions after enrollment changes.
   - Mitigation: enforce send-time backend checks every request.
3. Risk: websocket interruption.
   - Mitigation: reconnect + backfill + graceful HTTP fallback.
4. Risk: abuse via open instructor contact.
   - Mitigation: request-gated entry for non-enrolled learners and moderation phase rollout.

## 19. Acceptance Criteria

1. Users can send and receive messages in real-time with no page refresh.
2. All conversation subscriptions are private and participant-authorized.
3. Learner-to-instructor non-enrolled initiation creates request, not active conversation.
4. Context chat is available for module/lesson/quiz with proper lineage validation.
5. Conversation list, unread counts, and message stream update live.
6. System remains functional when websocket is temporarily unavailable.
7. TailAdmin-aligned visual consistency is preserved in all role panels.
