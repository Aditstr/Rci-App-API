import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const membershipView = readFileSync(
    new URL('../../resources/js/views/client/MembershipView.vue', import.meta.url),
    'utf8',
);

const walletPage = readFileSync(
    new URL('../../resources/views/client/wallet.blade.php', import.meta.url),
    'utf8',
);

test('membership top-up link points at the client wallet page', () => {
    assert.match(membershipView, /href="\/client\/wallet"/);
    assert.doesNotMatch(membershipView, /href="\/wallet"/);
});

test('wallet page links back to the membership page', () => {
    assert.match(walletPage, /href="\/client\/membership"/);
});

test('wallet page submits manual top-up to the existing API endpoint', () => {
    assert.match(walletPage, /fetch\('\/api\/v1\/rci\/topup\/manual'/);
});
