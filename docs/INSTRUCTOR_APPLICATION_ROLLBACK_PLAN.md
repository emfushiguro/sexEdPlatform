# Instructor Application Rollback Plan

## Purpose
This document provides rollback procedures for the instructor application and lifecycle feature.

## 1. Roll Back Migrations
If deployment must be reverted immediately:
1. Put application in maintenance mode.
2. Roll back latest migrations:
   - php artisan migrate:rollback --step=3
3. Confirm rollback status and schema integrity.

If other unrelated migrations are in the same batch, use an explicit rollback strategy in staging first.

## 2. Restore Database Backup
If schema rollback alone is insufficient:
1. Restore pre-deployment backup.
2. Validate users, roles, and application tables after restore.
3. Confirm app can boot and authenticate.

## 3. Revert Application Code
1. Revert to last stable release tag/commit.
2. Clear caches:
   - php artisan config:clear
   - php artisan route:clear
   - php artisan view:clear
3. Restart queue workers and web processes.

## 4. Handle Existing Instructor Applications
Depending on rollback scope:
- If tables remain: keep records and disable routes/controllers temporarily.
- If tables are removed: export instructor application and role transition data before rollback.
- Preserve audit records whenever possible.

## 5. Validation Checklist After Rollback
- Login works for admin, learner, and instructor accounts.
- /instructor/login behavior is as expected for the restored version.
- Dashboard redirects are correct by role.
- No migration errors in logs.
- Notifications and queue workers are stable.

## 6. Staging Rehearsal Requirement
Before production rollback execution:
1. Rehearse rollback in staging using a fresh backup.
2. Record exact command sequence and timings.
3. Verify data integrity and user role consistency.
4. Share post-rehearsal report with deployment owners.
