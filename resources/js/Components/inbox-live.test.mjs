// Run: node --test resources/js/Components/inbox-live.test.mjs
import { test } from 'node:test';
import assert from 'node:assert/strict';
import inboxLive from './inbox-live.js';

const INTERVAL = 50;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function makeComponent() {
    const component = inboxLive('7', INTERVAL);
    component.refreshes = 0;
    component.$wire = { $refresh: () => component.refreshes++ };

    return component;
}

test('first event refreshes immediately', () => {
    const c = makeComponent();
    c.scheduleRefresh();
    assert.equal(c.refreshes, 1, 'a quiet inbox must update without waiting');
});

test('a burst collapses into one leading and one trailing refresh', async () => {
    const c = makeComponent();

    for (let i = 0; i < 20; i++) c.scheduleRefresh();
    assert.equal(c.refreshes, 1, 'burst must not fire one refresh per message');

    await sleep(INTERVAL * 2);
    assert.equal(c.refreshes, 2, 'the burst still needs one trailing refresh');
});

test('the last event of a burst is never dropped', async () => {
    const c = makeComponent();

    c.scheduleRefresh(); // leading
    await sleep(INTERVAL / 2);
    c.scheduleRefresh(); // arrives mid-window; must still be reflected

    await sleep(INTERVAL * 2);
    assert.equal(c.refreshes, 2, 'state after the final message must reach the UI');
});

test('spaced-out events each refresh straight away', async () => {
    const c = makeComponent();

    c.scheduleRefresh();
    await sleep(INTERVAL * 1.5);
    c.scheduleRefresh();

    assert.equal(c.refreshes, 2, 'idle inbox must not be throttled');
});

test('destroy cancels a pending trailing refresh', async () => {
    const c = makeComponent();

    c.scheduleRefresh();
    c.scheduleRefresh(); // queues the trailing one
    c.destroy();

    await sleep(INTERVAL * 2);
    assert.equal(c.refreshes, 1, 'a torn-down component must not touch $wire');
});
