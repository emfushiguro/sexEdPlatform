# Educational Events Operations Guide

Educational Events use the existing `seminars` records and registration workflow. Existing native Agora webinars continue to use the native livestream and duration-based attendance paths.

## Event formats

New events use one of these combinations:

| Event type | Format | Delivery details |
| --- | --- | --- |
| Seminar | In Person | Venue name and address; room and instructions are optional. |
| Seminar | External Platform | Select Google Meet, Zoom, Microsoft Teams, Google Classroom, or Other. Other requires a platform name and an HTTP or HTTPS URL. |
| Webinar | External Platform | Select a platform and supply its access URL. |

Native is retained for existing Agora webinars and is not offered for new authoring. An authorized connector manager submits an event for review; an administrator approves it before it can be published.

## External access

The external URL stays in the protected event record. Learner pages and notifications link to the event page and do not include the raw URL. The authenticated join route checks the event, URL, current access window, and the user's role or active registration again before redirecting.

- A confirmed, eligible registrant can join at the configured release time. A blank release time means the link is available immediately after confirmation.
- An accepted speaker, event manager, or administrator can open a valid link before participant release for event preparation.
- Pending, cancelled, or ineligible registrations do not grant learner access. Cancelled and archived events do not allow participant access.
- `Ongoing` is the default expiry and keeps a released link available after the event. `Event end` expires at the scheduled end. `Custom` expires at the selected date and time, which must be after release.
- A manager or administrator may replace the link and adjust its release or expiry after publication or completion. Delivery-change notices point to the protected event page and do not contain the URL.

Conscious Connections controls who can retrieve a link from this application. After a recipient has obtained or shared it, the external provider controls access to that link.

## Attendance

Connector managers and administrators can generate a code for an in-person or external event, optionally set its opening and closing times, and disable it. Codes are eight-digit numeric values generated with a secure random source. The database stores a password hash; the raw value is shown once after generation. Regenerating replaces the hash and invalidates the previous code. Do not put the raw code in messages or logs.

If no custom times are set, entry opens 15 minutes before the event starts and closes 30 minutes after it ends. Form times use the configured display timezone (`Asia/Manila` by default); timestamps are stored and processed in UTC. The default limit is five incorrect attempts per user and event, and twenty per IP address in a ten-minute window.

Code submission records one attendance row. It confirms that the learner submitted the event code; it does not prove continuous participation in an external event. The learner view labels this as “Attendance submitted.” Organizer-entered attendance is identified separately. Native Agora attendance keeps its measured join, leave, and duration data.

The management roster starts with registered participants, including those without an attendance row, and paginates at 25 participants per page. The CSV export streams registrations in batches of 200 and includes registration status, attendance status and method, timestamps, and total minutes. Manual attendance changes are limited to active confirmed registrations. Corrections/removals require a reason and are recorded in the activity log.

## Scheduler and queue worker

Laravel schedules `seminars:send-notices` every minute. It checks for event reminders during the hour before start and notices when a scheduled external link becomes available. Run the scheduler continuously in development with:

```sh
php artisan schedule:work
```

In production, configure the platform scheduler to invoke Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Delivery-change notifications implement Laravel's queued notification interface. The default queue connection is `database`; keep a queue worker running for that connection:

```sh
php artisan queue:work
```

If deployment configuration uses another queue connection, start the worker for that configured connection and monitor failed jobs using the deployment's normal operations process. The scheduler and queue worker serve different jobs: the scheduler discovers due notices, while the worker sends queued notifications.

## Database changes and tests

Educational Event schema changes use incremental migrations against the existing seminar tables. Review the migration and current database target before applying changes. In a controlled deployment, take the normal database backup and apply the migration without resetting existing tables:

```sh
php artisan migrate --pretend
php artisan migrate
```

The test suite uses the isolated `cc_db_test` database configured by `phpunit.xml`. Run focused checks with:

```sh
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Seminars
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Connectors/SeminarManualAttendanceTest.php
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/AdminSeminarAttendanceTest.php
```

Do not point tests at the development database. Never use `migrate:fresh`, `db:wipe`, database resets, destructive seeders, or table drops to test or deploy this feature; preserve existing development and event data.
