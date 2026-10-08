# Educational Events and External Delivery Design

## Goal and boundaries

Conscious Connections manages educational event authoring, moderation, discovery, registration, link access, notifications, and attendance. A physical venue or an external platform delivers the event. Existing Agora delivery remains available for legacy native webinars. This release does not add provider APIs, video conferencing, recordings, chat, screen sharing, QR check-in, payments, or a new event system.

The event remains a `Seminar` backed by `seminars`. User-facing pages call it an Educational Event and show both its event type (Seminar or Webinar) and format (In Person or External Platform). Existing `/seminars` routes and connector ownership remain stable.

## Existing foundation

- `seminars.type` currently contains `physical` or `webinar`. The connector authoring form uses `purpose` and `location`; `description` exists in the table but is not currently authored. `schedule` is required in the database, while `starts_at` and `ends_at` drive the current UI.
- `SeminarRegistrationService` handles participant eligibility, automatic or manual registration approval, capacity, cancellation, and confirmation notices. `seminar_registrants` is unique by seminar and user.
- Connector managers need `connector.manage_seminars`; `SeminarAccessService` also checks connector ownership. Admin moderation approves or rejects before a connector manager publishes.
- Agora uses `livestream_channel`, token and livestream services, and join/leave/heartbeat attendance. `seminar_attendances` is unique by seminar and user and currently stores native session duration.
- Existing confirmation, cancellation, publication, and live notifications use database and mail channels. A reminder notification class exists, but no event reminder schedule currently invokes it. The application already uses Laravel's scheduler for other domains.
- The development `seminars`, `seminar_registrants`, and `seminar_attendances` tables had zero rows on 2026-09-28. The migration must still preserve records in every other environment.

## Event classification and migration

Use `seminars.type` for the event type, with values `seminar` and `webinar`, and add `seminars.event_format` with values `in_person`, `external`, and `native`. A forward migration maps old `physical` rows to `seminar` plus `in_person`, and old `webinar` rows to `webinar` plus `native`. Existing Agora channels, livestream state, speaker assignments, registrations, and attendance remain intact. A webinar with missing native setup remains readable as a legacy incomplete event; it is not assigned an invented external URL.

New authoring permits an in-person or external seminar, or an external webinar. Native is retained on existing records and in the delivery code, but is hidden from new authoring. Existing native webinars can still be managed and joined through their current routes. Native livestream, token, heartbeat, comments, and Q&A paths must check `event_format = native`, so an external webinar cannot enter Agora flows merely because its type is `webinar`.

New columns are nullable or have safe defaults during the migration. Existing published events remain published and visible. Records lacking newly required details keep their legacy display until an authorized manager edits, submits, or publishes them; that action must supply the applicable details. A migration reversal must refuse to proceed if it would discard new-format data or force it into an incorrect legacy classification. No table reset, replacement, destructive seeding, or silent placeholder backfill is allowed.

## Event fields

| Existing field | New use |
| --- | --- |
| `type` | Seminar or Webinar after the value backfill. |
| `description` | Event description, restored in authoring. Existing nulls remain valid until edit or republication. |
| `purpose` | Objectives, relabeled in the UI without moving historical content. |
| `location` | Venue name for in-person events; historical free text remains visible. |
| `schedule` | Compatibility value kept synchronized with `starts_at`. |
| `connector_id`, `speakers` | Organizer and existing instructor/speaker assignments; no second ownership model. |

Add the following fields to `seminars` through incremental migrations:

- `event_format`.
- `venue_address`, nullable `venue_room`, and `delivery_instructions`. `location` continues to hold the venue name.
- `external_platform` (`google_meet`, `zoom`, `microsoft_teams`, `google_classroom`, `other`), `external_platform_name` for Other, and `external_url`.
- `external_link_visible_at`: null means immediately after confirmed registration; a timestamp means scheduled release.
- `external_link_expiry_mode`: `ongoing` by default, `event_end`, or `custom`. `external_link_expires_at` holds the custom expiry only. The effective expiry for `event_end` is `ends_at`; `ongoing` has no expiry. A custom expiry must be after release.
- `registration_deadline_at`: null means registration closes at event start.
- `attendance_code_hash`, `attendance_code_enabled`, `attendance_code_generated_at`, `attendance_start_at`, and `attendance_end_at`. Null attendance bounds use the defaults described below.
- Idempotency timestamps for the scheduled reminder and link-availability notifications.

Extend `seminar_attendances` with `attendance_method` (`native`, `attendance_code`, `manual`, or `legacy`) and nullable `attended_at`. Preserve `joined_at`, `left_at`, `total_seconds`, and the existing unique seminar/user constraint. Existing native rows retain their status and duration and receive the `native` method. If a legacy `seminar_registrants` row is already marked attended but has no attendance row, backfill one with method `legacy` and its recorded timestamp. New code and manual attendance keep registration status as `registered`, so active-registration checks remain valid. Mirror new `attended_at` values to the existing registrant field in the same transaction for compatibility, while `seminar_attendances` is the attendance source of truth.

## Authoring, lifecycle, and validation

Extend the connector create/edit form and current Form Requests. Alpine shows only the fields relevant to the selected format; server validation makes the same decision independently. The form displays local Philippine time and persists UTC timestamps as it does today.

New create and update requests require title, description, objectives, event type and format, existing audience/category rules, future start on creation, end after start, positive capacity when set, and an explicitly chosen registration deadline earlier than start. A null deadline uses event start. In-person events require a venue name and address; room and instructions are optional. External events require a listed platform, a custom name for Other, and an absolute HTTP or HTTPS URL. Provider-specific credentials and OAuth settings are outside scope. Review submission and publication recheck the persisted event so direct endpoint calls cannot bypass format requirements. Legacy records must meet the applicable rules on edit, review submission, or publication.

Preserve the `draft -> pending_review -> approved -> published` governance flow and the existing cancelled/completed/archived states. Connector managers retain authoring rights and ownership checks. Admins retain moderation rights and gain access to a narrowly scoped delivery-link management action; this does not grant general editing of connector content. Once registrations exist, type and format cannot change, capacity cannot fall below active registrations, schedule changes are rejected (a different schedule requires cancelling this event and creating a replacement through normal review), and audience changes cannot make current registrants ineligible. The separate delivery-link action can change a published or completed event's URL and access settings without changing its type, format, schedule, or audience.

The registration service enforces `registration_deadline_at` when present and otherwise closes at start. Only a confirmed registration (`status = registered` and no cancellation) grants participant link or code access; a pending application does not.

## External-link access and notifications

The learner event listing and detail show the type, format, organizer, speakers, date and time, registration state, and either venue information or the external platform label. Normal discovery lists published events for eligible audiences. Accepted speakers and authorized managers/admins can open a published event detail even when they do not match its audience filter. Confirmed registrants and accepted speakers can also open the detail of a completed event to reach an ongoing external resource; the existing discovery check must be adjusted for these cases. The URL is never placed in public page data, hidden fields, unauthenticated JSON, or notifications. A controlled GET join route rechecks event state, user identity, eligibility or speaker/management role, registration where required, release time, and expiry immediately before redirecting to the stored URL.

- Confirmed, still-eligible registrants can open the route only after release and before an optional expiry, while the event is published or completed.
- Accepted speakers can access a valid link before participant release to prepare, but observe an optional expiry. Connector seminar managers and admins can view/manage delivery details through authorized management screens.
- Cancelled or archived events do not provide participant access. Missing legacy URLs produce an unavailable state rather than an unsafe redirect.
- The default expiry mode is ongoing, allowing a released Google Classroom or other persistent resource to remain available after event completion. Organizers may choose event end or a custom date/time instead. A manager or admin may replace the URL and adjust access after completion, provided the event is not cancelled or archived.

Published- or completed-event changes to the URL, release time, expiry mode/time, or access instructions notify all affected confirmed registrants who still meet eligibility and all accepted speakers. If an admin makes the change, the connector organizer also receives the notice. Recipient IDs are deduplicated. Unregistered users and pending or cancelled registrants do not receive a protected-link notice. The mail and in-app message describes the change and points to the protected event page; it contains no raw URL. Changes made before publication do not send participant notices. Changing several delivery fields in one save sends one notice per recipient.

Use the current notification channels for registration confirmation and cancellation. Schedule one event reminder and a link-availability notice for each active scheduled release. A changed release time rearms the availability notice for the new schedule; an atomic sent marker prevents duplicate sends for the same schedule. Reminder and release notices go to active, still-eligible registrants and accepted speakers. A link-change notice is queued promptly after a successful save. Existing publication and native-live notices remain in their respective flows. Notification sending must not expose the URL or roll back an otherwise valid event update if an email delivery fails.

Conscious Connections controls who can obtain the external URL from its own application. Once a recipient has obtained or shared it, the external platform controls access and any invalidation of an old link.

## Attendance submission and management

An authorized connector manager or admin can enable a code for an in-person or external event, generate or regenerate it, set an optional opening and closing time, disable it, and inspect submissions. Generate an eight-digit numeric code with a cryptographically secure random source, store only its hash, and show the raw code once at generation. A manager who loses it regenerates it; regeneration immediately invalidates the previous code. Do not place the raw code in notifications, logs, or browser data for ordinary participants.

When no custom bounds are set, code entry opens 15 minutes before scheduled start and closes 30 minutes after scheduled end. The UI shows Philippine local time; stored overrides use UTC. The event must be published or completed, not cancelled or archived. Submission requires authentication, an active confirmed registration, current audience eligibility, enabled code, an open window, and a matching hash. Rate-limit failed attempts per user and event and per IP; the implementation plan will use five failures per ten minutes for each user/event and twenty failures per ten minutes per IP as the initial limits.

On success, write or update the single `seminar_attendances` row with `status = attended`, `attendance_method = attendance_code`, and `attended_at = now()` in a transaction. Repeated submissions return a clear already-submitted result and create no second record. Code entry is available to registered learners and instructors, matching the existing audience model. It is optional for both in-person and external formats. Native events keep their duration-based attendance path.

Connector seminar managers and admins can manually mark a registered participant attended or remove/correct that decision. A reason is required for correction/removal, and the existing `ActivityLog` records actor, event, participant, action, and before/after state. Manual removal sets `status = not_present`, `attendance_method = manual`, and clears both attendance and registrant `attended_at` in one transaction without deleting the row or native join history. Completion runs duration finalization only for native attendance; it does not replace code or manual decisions. Native heartbeat/finalization must preserve an explicit manual override while still retaining measured duration. A later code submission cannot replace a manual override; an authorized manager must correct it. Unregistered walk-ins must register before they can be marked. Ordinary participants cannot edit attendance.

The management list joins registrations and attendance so it shows participants without a submission as well as those marked present, with status, method, time, and authorized actions. Paginate the list and update the existing CSV export with the same distinctions. Learners see “Attendance submitted” for a code entry and clear wording for host-entered or native activity. Code entry confirms submission of the event code; it does not independently prove continuous participation in an external event.

## Verification and documentation

Use the isolated `cc_db_test` test database configured in `phpunit.xml`; never use the development database as a disposable test database. Migration tests cover legacy `physical`, `webinar`, registrations, attendance, venue text, and Agora data. Feature and unit tests cover authoring combinations and validation, ownership and admin permissions, manual registration approval, link release/expiry boundaries, unauthorized URL leakage, URL replacement and recipient notifications, scheduled-notice idempotency, code generation and throttling, duplicate submission, attendance window boundaries, manual corrections and audit entries, and native Agora regression. Update the event architecture and attendance limitations documentation alongside the implementation.

The implementation is complete when existing relationships and records remain usable, new events follow the allowed type/format combinations, external links are protected and configurable, affected registered participants and speakers receive link-change notices, attendance is recorded once through code or authorized manual action, and native Agora delivery remains functional for existing webinars.
