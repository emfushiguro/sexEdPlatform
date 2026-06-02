# Connector Seminars Design

## Goal

Build a connector-owned free Seminar/Webinar system that lets verified connector organizations host scheduled community events for learners, instructors, and other authorized participants. The first release includes the full free seminar flow: connector management, registration, speaker assignment, Agora livestream access, realtime comments and Q&A, viewer count, attendance tracking, notifications, exports, and admin moderation.

Paid seminar access, seminar-specific Paymongo checkout, Agora Cloud Recording, hybrid events, multi-session seminars, and physical-seminar attendance automation are outside this first release.

## Existing Context

The project already has:

- A Laravel 12 application using Blade, Alpine, Tailwind, Laravel Echo, and Reverb.
- A Connector domain with verified connector workspaces, connector memberships, connector roles, connector-scoped permissions, and connector subscription visibility.
- Existing `Seminar` and `SeminarRegistrant` models that are currently lightweight and should be reused rather than replaced.
- Existing connector route ownership through `routes/connector.php`.
- Existing notification, admin, learner, instructor, and moderation patterns.

The seminar implementation should extend the existing models and conventions instead of creating a disconnected event system.

## Scope

In scope:

- Connector seminar list, create, edit, details, registrants, attendance, comments/Q&A moderation, exports, and livestream pages.
- Connector-owned free seminars and webinars.
- Seminar statuses: `draft`, `published`, `cancelled`, `completed`.
- Seminar types: `webinar` and `physical`.
- Connector permission enforcement through `connector.manage_seminars`.
- Verified connector requirement for creating and publishing seminars.
- Participant browsing for authenticated and email-verified learners and instructors.
- Participant registration, cancellation before start, capacity control, and duplicate prevention.
- Learner age category targeting using the existing learner categories: `kids`, `teen`, and `adult`.
- Speaker assignment with platform-user speakers and external display-only speakers.
- Agora RTC live broadcasting for webinars.
- Laravel Echo/Reverb realtime comments, Q&A, viewer count, and live status updates.
- Attendance tracking from livestream join/leave activity.
- Registration confirmation, reminder, cancellation, and speaker assignment notifications.
- Admin seminar moderation dashboard, seminar detail review, comment/Q&A moderation, cancellation/suspension, attendance review, and exports.
- Focused unit/feature tests, including livestream access and Agora token role tests with mocked token generation.

Out of scope:

- Paid seminars and Paymongo seminar payment flow.
- Agora Cloud Recording.
- Multi-session or multi-day seminars.
- Hybrid seminars that are both physical and livestreamed.
- External speaker livestream access.
- Public guest registration.
- QR check-in or manual physical attendance tracking.

## Ownership And Access

Each seminar belongs to exactly one connector through `seminars.connector_id`.

Connector-side seminar management requires:

```text
authenticated user
+ verified email
+ verified connector
+ active connector membership
+ connector role permission: connector.manage_seminars
```

Only verified connectors can create or publish seminars. Connectors can manage only seminars where `seminars.connector_id` matches their connector. Connector members without `connector.manage_seminars` can view only the pages available to their role.

Connector subscription entitlement is not required for this first free seminar release. The design keeps permission and entitlement concepts separate so future paid or plan-gated seminar features can be added without changing the connector permission boundary.

Admin users moderate across connectors from admin routes. Admins can review, hide interaction records, and cancel/suspend seminars with reasons. Admins should not casually edit connector-owned seminar content.

## Seminar Lifecycle

Statuses:

- `draft`: connector can edit all fields; not visible to participants.
- `published`: visible to eligible learners and instructors; registration is open until capacity is reached or the seminar starts.
- `cancelled`: no registration or livestream access; records remain visible for reporting; registrants are notified.
- `completed`: no registration or livestream access; participant pages show completed state; attendance is finalized.

Draft seminars are fully editable. Published seminars are editable with restrictions once registrations exist:

- `starts_at` and `ends_at` cannot be changed to invalidate existing access expectations without a cancellation/republication flow.
- `capacity` cannot be reduced below the current active registration count.
- `target_participants` and `learner_age_categories` cannot be changed in a way that makes existing registrants ineligible.
- `type` cannot be changed after registration exists.

Connector seminar managers manually mark seminars completed. Completion finalizes attendance records from livestream join/leave data.

## Seminar Data Model

Extend `seminars` with connector ownership, lifecycle, schedule, eligibility, and moderation fields:

- `connector_id`
- `type`
- `title`
- `description`
- `purpose`
- `category`
- `status`
- `starts_at`
- `ends_at`
- `capacity`
- `target_participants`
- `learner_age_categories`
- `location`
- `livestream_channel`
- `cancelled_at`
- `cancelled_by`
- `cancellation_reason`
- `completed_at`
- `completed_by`
- `admin_moderation_status`
- `admin_moderation_reason`
- timestamps

Config-backed seminar categories:

- `education`
- `awareness`
- `health`
- `community`
- `other`

Target participant values:

- `learners`
- `instructors`
- `learners_and_instructors`

Learner age category values:

- `kids`
- `teen`
- `adult`

Learner age category filtering applies only when learners are eligible. Instructors are not filtered by learner age category.

## Registration And Eligibility

Authenticated and email-verified learners and instructors can browse published seminars. Registration succeeds only when:

- The seminar is `published`.
- The seminar has not started.
- The user matches participant type eligibility.
- Learners match at least one selected learner age category.
- Capacity has not been reached.
- The user is not already actively registered.

Registration is automatic when the checks pass. No connector or admin approval is required. Duplicate registrations are blocked with a unique seminar/user rule. Participants can cancel their registration before `starts_at`. Registration closes when capacity is reached or the seminar starts.

Completed seminars remain visible with a completed state and no join access. Cancelled seminars remain visible with cancellation messaging and no join access.

## Speakers

Speakers can be:

- Platform users.
- External display-only speakers.

Platform-user speaker selection should prioritize instructors in the UI, while still allowing eligible existing users to be selected. Assigned platform-user speakers appear on seminar details and can join webinars as Agora speakers with publish privileges. External speakers appear in seminar details but cannot join livestreams in this first release.

Connector members with `connector.manage_seminars` act as hosts for webinars and can publish audio/video.

## Agora Livestream

Use Agora RTC live broadcasting mode.

Environment and config:

```text
AGORA_APP_ID=
AGORA_APP_CERTIFICATE=
AGORA_TOKEN_TTL_SECONDS=900
```

Expose these through `config/services.php` under `services.agora`.

Laravel generates short-lived role-specific Agora tokens. The application must never expose `AGORA_APP_CERTIFICATE` to the browser.

Agora role mapping:

- Host: connector seminar manager; publish and subscribe.
- Speaker: assigned platform-user speaker; publish and subscribe.
- Audience: registered eligible participant; subscribe only.

Join access opens 15 minutes before `starts_at` and closes at `ends_at`, cancellation, or completion. Tokens should expire quickly, with a default TTL of 10 to 15 minutes. Authorized clients can refresh tokens through a protected endpoint during the active join window.

Agora UID should be deterministic from the platform user ID so join/leave events and attendance records can be associated reliably.

## Livestream Join Experience

Participants use the seminar detail page to see registration state and join availability. Registered eligible users can enter the join page only during the join window. Hosts and speakers see publish controls. Audience members see the stream and interaction panels only.

Viewer count should track:

- Audience count.
- Host/speaker presence.

Audience members must not receive publish-capable Agora tokens. Assigned speakers must not receive publish-capable tokens unless they are assigned to that seminar. Connector seminar managers must not host seminars outside their connector.

## Realtime Comments And Q&A

Use Laravel Echo/Reverb for:

- Comments.
- Q&A.
- Viewer count.
- Live seminar status updates.

Comments and Q&A are separate streams. Messages appear immediately after authorization succeeds. Connector seminar managers and admins can hide messages. Hidden records are status-marked with moderator metadata and are not deleted.

Comment status values:

- `visible`
- `hidden`

Q&A status values:

- `pending`
- `answered`
- `hidden`

Q&A records may be pinned. After completion, participant interaction becomes read-only. Connector and admin users retain reporting and moderation access.

## Attendance Tracking

Attendance is tracked from livestream page events for webinars:

- joined time
- left time
- total duration
- attendance status

A user counts as attended after a configurable minimum duration, default 5 minutes. Multiple join/leave segments for the same seminar/user should be combined into one attendance summary.

Attendance statuses:

- `registered`
- `joined`
- `attended`
- `left`

Physical seminar attendance automation is out of scope for this release. Physical seminar registration still works, but physical attendance tracking can be added as a separate feature.

## Admin Moderation

Admins get a seminar moderation dashboard with filters by:

- status
- connector
- category
- date
- moderation state

Admin detail pages show seminar metadata, connector ownership, registrants, attendance, comments, Q&A, and moderation actions.

Admin actions:

- View all connector seminars.
- Cancel or suspend inappropriate seminars with a required reason.
- Hide comments and questions.
- Review attendance and registrants.
- Export registrant and attendance records from admin context when the current user passes the same admin authorization used for the moderation dashboard.

Admin cancellation closes registration and livestream access, preserves records, and notifies registrants.

## Connector UI

Connector pages:

- Seminar list.
- Create seminar.
- Edit seminar.
- Seminar details.
- Registrants.
- Attendance.
- Livestream host/join page.
- Comments and Q&A moderation.
- Registrant and attendance CSV exports.

The UI should follow existing connector dashboard, Blade, Alpine, and Tailwind conventions. Forms should be compact and beginner-friendly. Livestream controls should expose only what the role needs: host/speaker publish controls and audience watch/interact controls.

## Participant UI

Learners and instructors get a shared authenticated seminar listing and detail flow.

Participant pages:

- Seminar listing.
- Seminar details.
- Registration/cancellation state.
- Livestream join page.
- Completed/cancelled read-only states.

Learners see and can register only for seminars matching their learner age category. Instructors see instructor-eligible seminars.

## Notifications

Use existing Laravel notification patterns for:

- Registration confirmation.
- Seminar reminder.
- Cancellation notice.
- Speaker assignment.
- Admin cancellation/suspension notice.

Reminder timing should be configurable, with at least one reminder before the seminar starts.

## Exports And Reporting

Connectors can export:

- Registrants CSV.
- Attendance CSV.

Connector exports are scoped to their own connector seminars. Admin exports use admin context and should follow existing admin authorization conventions.

## Error Handling And Safety

Registration errors should clearly distinguish:

- capacity reached
- duplicate registration
- registration closed
- ineligible participant type
- learner age category mismatch
- cancelled/completed seminar

Agora token endpoints fail closed for:

- unregistered audience user
- user outside allowed role
- wrong connector
- wrong seminar status
- outside join window
- unassigned platform-user speaker
- missing Agora config

Realtime write endpoints fail closed when the user is not registered, not a host/speaker, not a connector moderator, or not an admin according to the action being attempted.

## Testing Strategy

Use focused tests rather than network integration tests.

Feature tests should cover:

- connector seminar ownership
- connector permission gates
- draft/published/cancelled/completed lifecycle
- published edit restrictions
- participant browsing
- learner/instructor eligibility
- learner age category filtering
- capacity enforcement
- duplicate registration prevention
- registration cancellation before start
- speaker assignment
- join-window authorization
- Agora role token authorization
- audience publish denial
- comments and Q&A creation
- comments and Q&A hiding
- viewer count updates
- attendance duration and attended threshold
- admin moderation actions
- CSV exports
- notifications

Unit tests should cover:

- seminar access service
- registration service
- Agora token service using mocked token builder behavior
- attendance aggregation service
- comment/Q&A moderation service

Tests must not call Agora over the network. Agora token generation should be tested through service boundaries and mocked token builders.

## Implementation Order

Implementation should be ordered as:

1. Schema and model extensions.
2. Connector seminar management.
3. Participant listing and registration.
4. Speaker assignment.
5. Agora token and join flow.
6. Realtime comments, Q&A, and viewer count.
7. Attendance tracking.
8. Admin moderation.
9. Notifications and exports.
10. Verification and polish.

This keeps the feature reviewable while delivering the full free seminar experience requested.

## Acceptance Criteria

- Verified connector organizations can create and manage free seminars.
- Connector seminar managers need `connector.manage_seminars`.
- Connectors can manage only their own seminars.
- Seminars support draft, published, cancelled, and completed states.
- Learners and instructors can browse eligible published seminars.
- Learner registration respects kids, teen, and adult targeting.
- Capacity and duplicate registration rules are enforced.
- Assigned platform-user speakers can join webinars as speakers.
- External speakers are display-only.
- Registered eligible participants can join webinars only during the allowed join window.
- Agora credentials remain server-side.
- Audience members cannot publish audio/video.
- Comments and Q&A are realtime, persisted, and moderateable.
- Attendance tracks join/leave duration and attended status.
- Admins can moderate seminars and interactions across connectors.
- Registrants and attendance can be exported.
- Paid seminar access and recording are not part of this first release.
