import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';
const database = process.env.PLAYWRIGHT_DB_DATABASE ?? '';
const runId = process.env.PW_RUN_ID ?? `pw-${Date.now()}`;
const configCache = path.resolve('.playwright', 'config.php');

export default defineConfig({
    testDir: './tests/Browser',
    testMatch: /direct-instructor-.*\.spec\.mjs$/,
    globalSetup: './tests/Browser/Support/global-setup.mjs',
    fullyParallel: false,
    workers: 1,
    timeout: 30_000,
    expect: { timeout: 10_000 },
    reporter: [['html', { open: 'never' }], ['line']],
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        { name: 'chromium-desktop', use: { ...devices['Desktop Chrome'] } },
        { name: 'chromium-mobile', use: { ...devices['iPhone 13'] } },
        { name: 'firefox-smoke', grep: /@firefox-smoke/, use: { ...devices['Desktop Firefox'] } },
    ],
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: baseURL,
        reuseExistingServer: false,
        timeout: 120_000,
        env: {
            APP_ENV: 'testing',
            APP_CONFIG_CACHE: configCache,
            DB_DATABASE: database,
            PLAYWRIGHT_DB_DATABASE: database,
            SESSION_DRIVER: 'file',
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
            MAIL_MAILER: 'array',
            PERSONA_ENABLED: 'false',
            DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED: 'true',
            INSTRUCTOR_IDENTITY_ENFORCED_FROM: '2026-01-01T00:00:00+08:00',
            PW_RUN_ID: runId,
        },
    },
});
