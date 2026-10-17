# Connector instructor negotiation messaging

Approved in chat on 2026-10-17: private organizer/instructor threads, with seminar/webinar hosts able to manage educators by default.

- Reuse the existing chat UI, messages, unread counts, reports and notifications. The Educators directory lists active instructors with search and pagination. Its Message action opens a private thread.
- Effective `connector.manage_educators` access includes `connector.manage_seminars`; a verified Connector and active membership are required. Owners already have both permissions. This applies to existing host roles without rewriting memberships or permissions.
- A thread belongs to one Connector and one organizer/instructor pair. Add nullable `conversations.connector_id` with an incremental migration. For the new `connector_negotiation` type, participant one is the organizer and participant two is the instructor; pair keys remain normalized. Context keys include both Connector and organizer IDs so reversing the participants' responsibilities creates a separate thread. Existing conversation types keep their behavior.
- Only the two participants can access a thread. Recheck the Connector status, organizer membership/permission, participant status and instructor role for list, history, sending, reads, reports, attachments and realtime subscription. Losing eligibility disables access on both sides while preserving history.
- Organizers create threads through the authorized Connector endpoint. Instructors reply through their existing inbox. Negotiations require no enrollment or subscription. Generic chat start cannot create negotiation threads.
- Negotiation attachments use private local storage and authenticated downloads. Existing public learning-chat attachments retain their behavior. Queued broadcasts and new notifications recheck negotiation eligibility.
- Connector messages use the Connector layout and filter the shared conversation list to that Connector. Instructor inboxes show the Connector name as context. Directory and inbox have empty states; send failures use existing chat feedback.
- Messages record discussions only. They do not alter prices, checkout, entitlements, contracts or financial history.

Validation: focused HTTP tests for host/default access, delegated access, isolation, repeat starts, replies, revocation, private attachments, context and notifications; existing chat and Connector permission regressions; PHP formatting, JS syntax and Vite build. Use only the isolated `cc_db_test` database for tests. Apply only the new forward migration to development after inspecting the current schema; never reset data. Browser/manual and staging checks remain separate release gates.
