import { test } from 'node:test';
import assert from 'node:assert/strict';
import {
    createMembershipStore,
    formatRupiah,
    isMembershipActive,
    purchaseKey,
} from '../../resources/js/utils/membership.js';

function memoryStorage() {
    const values = new Map();

    return {
        getItem: (key) => (values.has(key) ? values.get(key) : null),
        setItem: (key, value) => values.set(key, String(value)),
        removeItem: (key) => values.delete(key),
    };
}

test('a generated key is persisted and reused on retry', () => {
    const store = createMembershipStore(memoryStorage(), '7');
    const crypto = { randomUUID: () => 'key-1' };

    assert.equal(purchaseKey(store, crypto), 'key-1');
    assert.equal(purchaseKey(store, { randomUUID: () => 'key-2' }), 'key-1');
    assert.equal(store.get(), 'key-1');
});

test('a key left by a previous attempt survives a reload', () => {
    const storage = memoryStorage();
    createMembershipStore(storage, '7').set('key-1');

    const afterReload = createMembershipStore(storage, '7');

    assert.equal(purchaseKey(afterReload, { randomUUID: () => 'key-2' }), 'key-1');
});

test('membership counts as active only when the server says is_pro', () => {
    assert.equal(isMembershipActive({ is_pro: true }), true);
    assert.equal(isMembershipActive({ is_pro: false }), false);
    assert.equal(isMembershipActive(null), false);
});

test('price is rendered as rupiah without decimals', () => {
    assert.match(formatRupiah(20000), /^Rp\s20\.000$/);
});
