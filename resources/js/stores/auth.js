import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('rci_token') || null,
        user: JSON.parse(localStorage.getItem('rci_user') || 'null'),
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        role: (state) => state.user?.role || null,
        userName: (state) => state.user?.name || 'Pengguna',
    },

    actions: {
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post('/auth/login', credentials);
                const data = response.data;
                const token = data.token || data.access_token;
                const user = data.user;

                this.token = token;
                this.user = user;

                if (token) {
                    localStorage.setItem('rci_token', token);
                }
                if (user) {
                    localStorage.setItem('rci_user', JSON.stringify(user));
                }

                return { success: true, user, role: user?.role };
            } catch (err) {
                const message =
                    err.response?.data?.message ||
                    err.response?.data?.errors?.email?.[0] ||
                    'Gagal masuk. Periksa kembali email dan kata sandi.';
                this.error = message;
                return { success: false, message };
            } finally {
                this.loading = false;
            }
        },

        async register(formData) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post('/auth/register', formData);
                const data = response.data;
                const token = data.token || data.access_token;
                const user = data.user;

                if (token) {
                    this.token = token;
                    localStorage.setItem('rci_token', token);
                }
                if (user) {
                    this.user = user;
                    localStorage.setItem('rci_user', JSON.stringify(user));
                }

                return { success: true, data };
            } catch (err) {
                const message =
                    err.response?.data?.message ||
                    'Registrasi gagal. Silakan periksa data input.';
                this.error = message;
                return { success: false, message, errors: err.response?.data?.errors };
            } finally {
                this.loading = false;
            }
        },

        async fetchUser() {
            if (!this.token) return;
            try {
                const response = await api.get('/auth/me');
                this.user = response.data.data || response.data.user || response.data;
                localStorage.setItem('rci_user', JSON.stringify(this.user));
            } catch (err) {
                // If token invalid, clear
                if (err.response?.status === 401) {
                    this.logout();
                }
            }
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/auth/logout');
                }
            } catch (e) {
                // Ignore logout network errors
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('rci_token');
                localStorage.removeItem('rci_user');
            }
        },
    },
});
