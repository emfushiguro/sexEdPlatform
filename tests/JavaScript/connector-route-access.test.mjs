import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';

test('connector discovery and registration use learner contact readiness', () => {
    const routes = JSON.parse(execFileSync('php', ['artisan', 'route:list', '--json', '--path=connectors'], {
        encoding: 'utf8', env: { ...process.env, APP_ROUTES_CACHE: 'bootstrap/cache/routes-connector-test-not-generated.php' },
    }));
    for (const name of ['connectors.index', 'connectors.register', 'connectors.store']) {
        const route = routes.find(item => item.name === name);
        assert.ok(route, name);
        assert.ok(route.middleware.includes('learner.contact-ready'), name);
        assert.ok(!route.middleware.includes('verified'), name);
    }
});
