# Admin-Only Email MFA Design

**Date:** 2026-11-10
**Status:** Approved design; pending written-spec review

## Summary

Conscious Connections will require every account with the `admin` role to complete email-based multi-factor authentication after password validation and before Laravel creates an authenticated Admin session. The system will issue a six-digit, five-minute code through the existing verification mailer, persist only a hash, and enforce completion through central Admin middleware.

Learner, Instructor, and Parent authentication will remain unchanged. A permission-only account without the `admin` role will not enter Admin MFA or gain Admin access. This design does not replace Laravel authentication, introduce a new email provider, or add trusted-device behavior.

## Existing Project Context

- `AdminAuthController` currently authenticates immediately with `Auth::attempt()` and redirects to the Admin dashboard after checking `access admin panel` permission.
- The Admin route group in `routes/admin.php` is protected by `auth`, `verified`, and `role:admin`.
- The general `/login` path currently authenticates first and then rejects accounts with `access admin panel` permission.
- Laravel Notifications already use `mail.verification_mailer` and branded Blade templates under `resources/views/emails`.
- The branded email language uses a gray page background, a white 560px card, a purple gradient, Conscious Connections branding, security callouts, and shared footer links.
- `ActivityLog` supports explicit actor IDs for events that occur before an authenticated session exists.
- Admin email changes already clear `email_verified_at` and dispatch a verification notification.

The implementation will align the login boundary with the existing Admin route boundary: the `admin` role, rather than a standalone permission, determines whether MFA is required and whether the Admin portal is available.

## Goals

- Require email MFA for every new Admin login.
- Keep the Admin unauthenticated until both factors succeed.
- Protect every Admin web route and Admin API endpoint server-side.
- Provide atomic, single-use OTP verification with expiration, attempt limits, resend limits, and safe concurrent behavior.
- Reuse the current mail transport, authentication layouts, visual language, activity logging, and test infrastructure.
- Force pre-deployment Admin sessions to complete MFA without invalidating unrelated user sessions.
- Preserve all existing non-Admin authentication behavior.

## Non-Goals

- Remembered or trusted devices.
- Backup or recovery codes.
- TOTP authenticator applications.
- SMS verification.
- WebAuthn or passkeys.
- An administrative MFA bypass.
- A new mail provider.
- A shared email-template refactor.
- Unrelated role, authentication, or UI refactoring.

## Selected Architecture

The feature will use a database-backed challenge and delayed authentication.

1. An authentication controller validates the submitted password without calling `Auth::login()` or `Auth::attempt()` for an Admin candidate.
2. The server confirms that the account has the `admin` role, is active, is not suspended, and has a verified registered email.
3. The server regenerates the pending session, creates a challenge, stores its UUID only in that session, and sends a six-digit code synchronously.
4. Dedicated guest-accessible MFA routes provide the verification page, verification, resend, and cancellation actions.
5. Successful verification atomically consumes the challenge before authenticating the Admin.
6. Laravel then authenticates the Admin, regenerates the session, and stores an MFA marker bound to the Admin and current credentials.
7. Central middleware rejects any Admin session without a current marker.

The general `/login` controller will inspect an email-based Admin candidate before calling the existing `LoginRequest::authenticate()` method. Admin candidates will use the shared MFA-start service; non-Admin requests will continue through the existing authentication method unchanged. The dedicated `/secure-panel-access` controller will call the same MFA-start service. This avoids duplicating the OTP lifecycle and avoids even a temporary authenticated Admin session.

## Components

The feature adds these focused units:

- `AdminMfaChallenge`: persistence and relationships for one pending login transaction.
- `AdminMfaService`: starts, resends, verifies, consumes, cancels, expires, and prunes challenges.
- `AdminMfaController`: renders the prompt and handles verify, resend, and cancel actions.
- `VerifyAdminMfaRequest`: validates a required six-digit numeric code.
- `EnsureAdminMfaVerified`: enforces the current session marker on protected Admin surfaces.
- `AdminLoginCodeNotification`: sends the plaintext code only through the synchronous mail call.
- `auth.admin-mfa`: the verification interface.
- `emails.admin-mfa-code`: the branded OTP email.
- A small Admin-only email-verification endpoint that prevents an unverified Admin from becoming permanently locked out after an email change.

Existing authentication controllers, routes, middleware aliases, logout handling, and Admin email-verification routing will receive only the changes needed to connect these units.

## Challenge Data Model

Create one normal incremental migration for `admin_mfa_challenges` with:

- UUID primary key.
- `user_id` foreign key to `users`.
- `code_hash` string.
- `session_fingerprint` string.
- `credential_fingerprint` string.
- Unsigned verification-attempt counter, defaulting to zero.
- Unsigned resend counter, defaulting to zero.
- `code_expires_at` timestamp.
- `login_expires_at` timestamp.
- `resend_available_at` timestamp.
- Nullable `delivered_at` timestamp.
- Nullable `delivery_failed_at` timestamp.
- Nullable `consumed_at` timestamp.
- Nullable `cancelled_at` timestamp.
- Nullable `locked_at` timestamp.
- Standard creation and update timestamps.

Indexes will support lookup by user and cleanup by terminal/expiration timestamps. The table will never contain the plaintext OTP or a duplicate destination email address.

The session fingerprint is a keyed digest of the current session identifier, so the raw identifier is never stored in the challenge. The credential fingerprint is a keyed digest of the user ID, current password hash, and normalized registered email, so a password or email change invalidates a pending challenge without storing another password hash.

Challenge state is derived from its timestamps rather than duplicated in a mutable status string. Terminal and expired records are pruned after 24 hours. Audit events remain under the existing activity-log retention policy.

## Initial Login Flow

1. Normalize email input through `AccountEmail`.
2. Apply the existing password-login limit of five attempts per minute per IP.
3. If the submitted identifier can belong to an Admin, retrieve the candidate through Laravel's user provider and validate the password without authenticating.
4. Require the `admin` role, active status, no active suspension, and a verified registered email.
5. Return the same public credential error for an invalid password, non-Admin account on the Admin portal, inactive or suspended Admin, or unverified Admin. This avoids role and account-state enumeration.
6. Apply challenge-start limits of five starts per 15 minutes using both a hashed account key and an IP key.
7. Regenerate the pending session and cancel only an earlier pending challenge attached to that same session.
8. Generate a zero-padded six-digit code with `random_int(0, 999999)` and hash it with Laravel's hashing service.
9. Set the code expiration to five minutes and the overall password-authenticated transaction expiration to 15 minutes.
10. Persist the challenge, place only its UUID in the server session, and dispatch the mail notification synchronously through `mail.verification_mailer`.
11. Mark delivery success or failure without logging the code. A failed delivery keeps the user unauthenticated and leads to the same verification screen with safe retry and cancellation options.

Both `/login` and `/secure-panel-access` start this flow for an account with the `admin` role. Username-based non-Admin login and every other role continue to use the existing `LoginRequest` behavior.

## Verification Flow

Verification runs in a database transaction and locks the selected challenge row.

The service checks, in order:

- The pending session contains the challenge UUID.
- The stored session fingerprint matches the current pending session.
- The related account still has the `admin` role.
- The account remains active, unsuspended, and email-verified.
- The credential fingerprint still matches.
- The challenge is delivered, unconsumed, uncancelled, unlocked, and within both expiration windows.

An incorrect code increments the attempt counter atomically. The fifth incorrect attempt sets `locked_at`; no further code can succeed. An otherwise valid code is checked with Laravel's hashing service and consumed inside the locked transaction.

Only after consumption does the controller:

1. Authenticate the Admin through the web guard.
2. Regenerate the session identifier.
3. Store `admin_mfa_verified_user_id`, `admin_mfa_verified_at`, and an authentication fingerprint in the new session.
4. Remove pending challenge state.
5. Record the success audit event.
6. Redirect to a validated intended Admin URL, falling back to `admin.dashboard`.

A repeated or concurrent submission sees a consumed challenge and cannot reuse the code.

## Resend, Expiration, and Cancellation

Resend also locks the challenge row before changing it.

- The first resend is available 60 seconds after the current issuance.
- No challenge may be resent more than three times.
- A resend replaces the stored hash immediately, so the previous code becomes invalid.
- The replacement code expires after five minutes or at `login_expires_at`, whichever occurs first.
- Resending never extends the 15-minute password-authenticated transaction.
- Successful delivery clears the prior delivery failure and records `delivered_at` for the replacement code.
- Failed delivery leaves the replacement code unusable and the Admin unauthenticated; a later permitted resend can recover the flow.

Once the 15-minute transaction expires, the Admin must return to login and enter the password again.

Cancellation marks the challenge cancelled, clears pending state, regenerates the session, records an audit event, and returns to Admin login. Logout invalidates the complete session, so both authenticated MFA state and any pending state are cleared.

## Admin Email-Change Verification

Admin email changes continue to set `email_verified_at` to `null` and send the existing branded verification notification. Requiring a verified email for MFA creates a lockout risk because the current Laravel verification endpoint requires an authenticated session.

To close that gap without authenticating an unverified Admin, Admin verification emails will use a separate temporary signed route that:

- Accepts only the intended Admin user ID and email hash.
- Is protected by Laravel's signature validation and the existing verification throttle.
- Rechecks the `admin` role and current email hash.
- Marks only the email address as verified.
- Never logs the visitor in or creates an MFA marker.
- Redirects back to Admin login, where password and MFA are still required.

Non-Admin users retain the existing authenticated email-verification route. This is an isolated compatibility change, not a replacement email-verification system.

## Session and Route Enforcement

Register an `admin.mfa` middleware alias and add it to the central Admin route group after `auth`, `verified`, and `role:admin`.

For each request, the middleware requires:

- An authenticated user.
- The `admin` role.
- An MFA marker for that exact user ID.
- A marker authentication fingerprint matching the current user credentials and registered email.

Web failures log out the stale Admin session, invalidate it, regenerate the CSRF token, and redirect to Admin login. JSON/API failures return an unauthenticated response with an MFA-required error and never redirect to HTML.

Implementation will inventory `routes/api.php` and attach the same enforcement to every Admin API endpoint. Future Admin endpoints inherit protection by remaining inside the central protected groups.

Sessions created before deployment have no MFA marker, so the first Admin request forces a fresh login. Other users are unaffected. Password or registered-email changes invalidate the marker; role and verified-email middleware continue to enforce their own current state.

## Verification Interface

The verification page reuses `<x-auth-split-layout>`, the current Admin logo treatment, typography, spacing, and purple gradient.

It contains:

- Heading: **Two-Step Verification**.
- A short explanation that a code was sent to the registered email.
- A masked destination such as `j***@c***.com`; the complete address is not rendered.
- Six labelled digit inputs with `inputmode="numeric"` and one-time-code autocomplete.
- Automatic forward focus, backspace navigation, and whole-code paste distribution.
- A single combined `code` value submitted to the server.
- An explicit Verify button; entering the sixth digit does not auto-submit.
- A server-seeded five-minute expiration countdown.
- A server-seeded resend cooldown countdown.
- Resend and cancel actions.
- Accessible loading, success, and error feedback.

Client-side countdowns and disabled states are guidance only. The server remains authoritative for every expiry, cooldown, attempt, and resend decision. Resend and cancellation use CSRF-protected POST requests.

## MFA Email Design

Create a dedicated `emails.admin-mfa-code` template rather than extending a shared template used by unrelated notifications.

The template follows the current email UI:

- `#f3f4f6` page background.
- Centered white card, 560px maximum width, 16px radius, and the current shadow.
- Header gradient using `#A30EB2`, `#730DB1`, and `#3B0CB1`.
- Existing **CC** logo card and Conscious Connections brand name.
- Header: **Admin Login Verification**.
- Subtitle: **Secure access to the Admin panel**.
- Personalized greeting and concise login-request explanation.
- A large, high-contrast, selectable six-digit code block.
- Plain guidance to select and copy the code into the verification page already open in the browser.
- A five-minute expiration notice.
- A warning box matching the current password-reset email, telling the recipient never to share the code and to ignore an unrequested login.
- Existing Home, Privacy Policy, Terms of Service, and copyright footer.
- Responsive padding and code sizing for narrow email clients.

Email clients do not reliably support JavaScript, so the template will not pretend to provide a scripted copy button. The code remains selectable text. No link or button contains or prefills the OTP, and the notification is synchronous so the plaintext code is not serialized into a queue payload.

## Errors and Recovery

Before a valid challenge exists, public errors remain generic. Once the browser holds a valid pending challenge, the verification page can safely distinguish:

- Incorrect code, including remaining attempts.
- Attempts exhausted.
- Code expired while the overall login remains resendable.
- Overall login expired.
- Challenge already consumed.
- Challenge cancelled.
- Resend cooldown.
- Resend limit reached.
- Delivery failure.

No failure path authenticates the Admin or offers a bypass. A delivery failure offers only resend when allowed or return to login.

## Audit Logging

Use `ActivityLog::log()` with an explicit Admin user ID because most events occur before authentication. Record:

- `admin_mfa.challenge_created`
- `admin_mfa.email_sent`
- `admin_mfa.email_failed`
- `admin_mfa.verification_failed`
- `admin_mfa.verification_succeeded`
- `admin_mfa.expired`
- `admin_mfa.resend_requested`
- `admin_mfa.cancelled`
- `admin_mfa.logout`

Safe metadata may include the internal challenge UUID, attempt count, delivery state, and source login route. The activity-log helper already captures IP address and user agent. Passwords, OTPs, OTP hashes, raw session identifiers, and complete authentication fingerprints are prohibited from logs and exception messages.

## Testing Strategy

Tests use the project's isolated test infrastructure and never treat the development database as disposable.

### Authentication and challenge tests

- Non-Admin login remains unchanged.
- Both Admin login entry points start MFA after valid credentials.
- An Admin is not authenticated before OTP success.
- Incorrect credentials and ineligible account states fail generically.
- A permission-only non-Admin does not enter Admin MFA.
- OTP storage contains a hash, never the plaintext value.
- Correct verification consumes the challenge and authenticates the Admin.
- Incorrect, expired, consumed, cancelled, locked, and undelivered challenges fail.
- Five incorrect attempts lock the challenge.
- Resend cooldown and three-resend limit are enforced.
- Resend invalidates the previous code and cannot extend the overall login.
- Challenges are isolated by user and pending session.
- Sequential duplicate and concurrent verification attempts cannot consume twice.
- Password, email, role, status, suspension, or verification changes invalidate pending state.
- Delivery failure leaves the Admin unauthenticated and allows only safe recovery.
- Cancellation and logout clear the relevant state.

### Enforcement tests

- Direct Admin web access before MFA is rejected.
- Protected Admin API access before MFA returns JSON failure.
- A legacy authenticated Admin session without an MFA marker is forced to log in again.
- An MFA marker for another user or stale credential fingerprint is rejected.
- Successful MFA grants normal Admin access.
- Learner, Instructor, and Parent route behavior remains unchanged.

### UI, email, and audit tests

- The verification page renders the required copy, masked email, six inputs, countdown data, resend, and cancel actions.
- Paste/focus behavior receives a browser smoke check.
- The email renders current branding, exact gradient colors, selectable code, expiration, and security warning.
- Every required audit event is written without an OTP or other secret.
- The Admin-only signed email-verification route verifies email ownership but never authenticates the visitor.

Verification evidence will include focused MFA tests, affected authentication and RBAC regression tests, the full PHPUnit suite, a browser flow check, and real SMTP or configured sandbox delivery evidence when credentials are available. Results will be reported separately; no unexecuted gate will be described as passing.

## Deployment

1. Review and back up according to the normal deployment process.
2. Apply the incremental challenge-table migration without resetting or reseeding existing data.
3. Deploy the application code and refresh route/config caches through the normal release process.
4. Confirm the existing `MAIL_VERIFICATION_MAILER` and sender settings are valid; no new provider variables are required.
5. Complete one real Admin password-plus-OTP login.
6. Confirm a pre-deployment Admin session is forced to reauthenticate.
7. Confirm a non-Admin login remains unchanged.
8. Confirm direct Admin web and API requests cannot bypass MFA.
9. Monitor delivery failures, lockouts, and rate-limit events after release.

## Acceptance Criteria

The feature is acceptable only when:

- A valid Admin password cannot by itself access any Admin web or API functionality.
- The Admin remains unauthenticated until a delivered, current, session-bound OTP is atomically consumed.
- Codes are hashed, single-use, five minutes in duration, and bounded by the 15-minute login transaction.
- Attempt, resend, challenge-start, and existing password limits are enforced server-side.
- Existing Admin sessions cannot indefinitely bypass the new requirement.
- Non-Admin authentication and sessions remain unchanged.
- Email and verification UI match existing Conscious Connections conventions.
- Delivery failure, cancellation, logout, and account changes fail closed.
- Focused, regression, full-suite, browser, and available mail-delivery evidence is reported accurately.
