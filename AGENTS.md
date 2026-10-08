# Development Safety Rules

## Database Protection — CRITICAL

NEVER reset, wipe, truncate, recreate, or destructively reseed the development database unless the user explicitly requests it in the current session.

Do NOT execute or recommend destructive database commands such as:

- php artisan migrate:fresh
- php artisan migrate:fresh --seed
- php artisan db:wipe
- TRUNCATE
- DROP DATABASE
- DROP TABLE
- DELETE FROM without an explicitly scoped and user-approved purpose
- database recreation/reset scripts
- destructive seeders/factories

Preserve all existing development data.

When database/schema changes are required:

1. Inspect the current schema first.
2. Create a normal incremental migration.
3. Apply the migration without resetting existing tables/data.
4. Verify the migration against the existing data.
5. Never replace existing data with seed data merely to test a feature.

For testing:

- Prefer existing test infrastructure.
- Use isolated test databases or transactions where appropriate.
- Do not use the development database as a disposable test database.
- Do not run destructive tests against the development database.

If a task appears to require resetting the database, STOP and ask the user for explicit confirmation before performing the destructive operation.

Preserving existing development data takes priority over convenience.