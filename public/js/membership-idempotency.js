(function (global) {
    'use strict';

    function createStore(storage, scope) {
        var storageKey = 'rci_membership_idempotency_' + scope;

        return {
            get: function () {
                try {
                    return storage.getItem(storageKey);
                } catch (e) {
                    return null;
                }
            },
            set: function (key) {
                try {
                    storage.setItem(storageKey, key);
                } catch (e) {
                    // Storage can be unavailable in private mode; the in-memory key still works.
                }
            },
            clear: function () {
                try {
                    storage.removeItem(storageKey);
                } catch (e) {
                    // Same as above.
                }
            }
        };
    }

    global.RciMembershipIdempotency = { createStore: createStore };
})(typeof window !== 'undefined' ? window : globalThis);
