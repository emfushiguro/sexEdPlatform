import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

test('activation countdown uses the server duration and expires while the page stays open', () => {
    const template = readFileSync(new URL('../../resources/views/components/activation-expiry.blade.php', import.meta.url), 'utf8');
    const expression = template.match(/x-data="([^"]+)"/)[1]
        .replace('{{ $minutes }}', '2')
        .replace('{{ $remainingMs }}', '61000');
    let clientNow = 250000;
    let tick;
    let cleared;
    const countdown = vm.runInNewContext(`(${expression})`, {
        Date: { now: () => clientNow },
        setInterval(callback, interval) { tick = callback; assert.equal(interval, 1000); return 7; },
        clearInterval(timer) { cleared = timer; },
    });

    countdown.init();
    assert.equal(countdown.minutes, 2);
    clientNow += 1000;
    tick();
    assert.equal(countdown.minutes, 1);
    clientNow += 60000;
    tick();
    assert.equal(countdown.minutes, 0);
    clientNow += 60000;
    tick();
    assert.equal(countdown.minutes, 0);
    countdown.destroy();
    assert.equal(cleared, 7);
});
