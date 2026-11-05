import { execFileSync } from 'node:child_process';
import path from 'node:path';

export default function globalSetup() {
    const database = process.env.PLAYWRIGHT_DB_DATABASE ?? '';
    const appEnvironment = process.env.APP_ENV ?? 'testing';

    if (appEnvironment !== 'testing') {
        throw new Error('Playwright requires APP_ENV=testing.');
    }
    if (!/_playwright_test$/i.test(database)) {
        throw new Error('Playwright requires PLAYWRIGHT_DB_DATABASE ending in _playwright_test.');
    }
    if (process.env.DB_DATABASE && process.env.DB_DATABASE !== database) {
        throw new Error('DB_DATABASE must match PLAYWRIGHT_DB_DATABASE.');
    }

    execFileSync('php', [
        path.resolve('tests/Browser/Support/prepare-direct-instructor-fixtures.php'),
        '--check-only',
    ], {
        stdio: 'inherit',
        env: { ...process.env, APP_ENV: appEnvironment, DB_DATABASE: database },
    });
}
