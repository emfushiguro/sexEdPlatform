# Shared chat access fix — 2026-10-17

## Root cause

The development `cc_db` database had no canonical `access chat` permission row. All three active instructor accounts failed `User::canAccessChat()`. Learners already had a role fallback; the existing admin account passed through the admin Gate override. Fresh test seeders had populated the missing permissions, so the earlier tests did not reproduce the development failure.

## Fix

- Extend the shared chat capability check to recognize existing admin/instructor classifiers, matching learner compatibility and retaining explicit grants and Connector organizer access.
- Recheck the persisted active-user record in `EnsureChatAccess`. Inactive, suspended, archived and soft-deleted accounts cannot open the page or retrieve inbox previews. Apply the same middleware to the `/admin/messages` alias.
- Recognize legacy instructors during contact discovery. Let instructors see their own pending requests when `manage message requests` is missing; acceptance/decline still require the intended recipient.
- Use `canAccessChat()` for instructor learner-list/detail message actions and admin relationship message actions.
- Preserve enrollment/request requirements, guardian verification/onboarding, conversation participant checks and private Connector negotiations. No permission reseeding, role rewrites, schema migration or development data reset was needed.

## Executed verification

| Check | Result |
| --- | --- |
| Chat feature/unit, Connector messaging and canonical RBAC regression matrix | **139 tests, 955 assertions passed** in isolated `cc_db_test`, executed serially. |
| New access matrix | **29 test cases passed**: admin/instructor/learner/parent under legacy, stale or missing permission states; non-active statuses; denied unrelated roles; explicit permission grants; instructor request acceptance and replies; visible instructor message actions; guardian requirements; guest authentication. |
| Existing development accounts through Laravel's full HTTP kernel | **41 successful GET checks**, all HTTP 200: `/chat`, `/chat/conversations`, `/chat/discovery`, `/chat/requests` for 3 instructors, 1 admin, 5 learners and 1 verified parent, plus the admin messages alias. These were server-side probes, not browser clicks. |
| Development preservation | Probe rendering ran inside a rolled-back transaction with array sessions/mail; user, conversation, message, subscription and notification counts were unchanged. Permission catalog and role assignments were not modified. |
| Scoped Pint | Passed for the new access test, shared middleware, conversation controller and updated Connector messaging test. |
| Additional PHP syntax | Passed for User, message-request controller and admin routes. |
| Route inspection | `/admin/messages` includes `chat.access` and `guardian.verified`. |
| Whitespace | `git diff --check` passed, with existing unrelated CRLF normalization notices. |
| Independent source review | No concrete blocker found in the role fallback, instructor discovery/request scope or persisted-status guard. |

Run the focused regression matrix with:

```powershell
php artisan test tests/Feature/Chat tests/Unit/Chat tests/Feature/Connectors/ConnectorInstructorMessagingTest.php tests/Feature/Rbac/RbacRoleCapabilityMatrixSeederTest.php tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php --compact --log-junit storage/framework/chat-access-regressions.xml
```

Evidence: `storage/framework/chat-access-regressions.xml` and the account-aggregate-only `storage/framework/chat-access-live-check.json`. The temporary diagnostic PHP script was removed.

## Remaining checks

Interactive browser/mobile/accessibility QA, live realtime delivery and the repository-wide test suite were not rerun for this fix. JavaScript and CSS did not change, so no asset rebuild was necessary. Refresh the existing `/chat` page to load the corrected server checks.
