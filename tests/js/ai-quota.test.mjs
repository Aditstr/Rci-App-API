import { test } from 'node:test';
import assert from 'node:assert/strict';
import { quotaLabel } from '../../resources/js/utils/ai-quota.js';

test('shows the remaining quota from the server response', () => {
    assert.equal(
        quotaLabel({ limit: 3, used: 1, remaining: 2 }),
        'Sisa 2 dari 3 pertanyaan AI hari ini',
    );
});

test('shows zero remaining after the quota is exhausted', () => {
    assert.equal(
        quotaLabel({ limit: 3, used: 3, remaining: 0 }),
        'Sisa 0 dari 3 pertanyaan AI hari ini',
    );
});

test('returns null when the server did not send a quota', () => {
    assert.equal(quotaLabel(null), null);
    assert.equal(quotaLabel({ limit: null, used: null, remaining: null }), null);
});
