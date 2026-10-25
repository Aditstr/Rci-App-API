import { test } from 'node:test';
import assert from 'node:assert/strict';
import '../../public/js/membership-idempotency.js';

function memoryStorage() {
    const values = new Map();

    return {
        getItem: (key) => (values.has(key) ? values.get(key) : null),
        setItem: (key, value) => values.set(key, String(value)),
        removeItem: (key) => values.delete(key),
    };
}

test('key survives a new store instance, simulating a reload', () => {
    const storage = memoryStorage();
    const beforeReload = globalThis.RciMembershipIdempotency.createStore(storage, '7');
    beforeReload.set('key-1');

    const afterReload = globalThis.RciMembershipIdempotency.createStore(storage, '7');

    assert.equal(afterReload.get(), 'key-1');
});

test('clear removes the key after a confirmed purchase', () => {
    const storage = memoryStorage();
    const store = globalThis.RciMembershipIdempotency.createStore(storage, '7');
    store.set('key-1');
    store.clear();

    assert.equal(store.get(), null);
});

test('different users do not share a key', () => {
    const storage = memoryStorage();
    const first = globalThis.RciMembershipIdempotency.createStore(storage, '7');
    const second = globalThis.RciMembershipIdempotency.createStore(storage, '8');

    first.set('key-1');

    assert.equal(second.get(), null);
});
