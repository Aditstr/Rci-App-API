import '../../../public/js/membership-idempotency.js';

export function createMembershipStore(storage, userId) {
    return globalThis.RciMembershipIdempotency.createStore(storage, userId);
}

/**
 * Reuse the stored key so a retry after a timeout cannot debit twice.
 */
export function purchaseKey(store, cryptoObj = globalThis.crypto) {
    const existing = store.get();

    if (existing) return existing;

    const key = cryptoObj?.randomUUID
        ? cryptoObj.randomUUID()
        : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

    store.set(key);

    return key;
}

export function isMembershipActive(status) {
    return status?.is_pro === true;
}

export function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value) || 0);
}

export function formatDate(value) {
    if (!value) return null;

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return null;

    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
