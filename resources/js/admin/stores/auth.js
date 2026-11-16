import { create } from 'zustand';
import { api, endpoints } from '../api';

export const useAuth = create((set, get) => ({
    user: null,
    status: 'loading', // 'loading' | 'guest' | 'admin'

    async fetchMe() {
        try {
            const { data } = await api.get(endpoints.auth.me);
            set({ user: data, status: data?.is_admin ? 'admin' : 'guest' });
        } catch {
            set({ user: null, status: 'guest' });
        }
    },

    async login(email, password, turnstileToken) {
        const payload = { email, password };

        if (turnstileToken) {
            payload.cf_turnstile_response = turnstileToken;
        }

        const { data } = await api.post(endpoints.auth.login, payload);
        set({ user: data.user, status: 'admin' });
        return data;
    },

    async logout() {
        try {
            await api.post(endpoints.auth.logout);
        } finally {
            set({ user: null, status: 'guest' });
        }
    },

    setGuest() {
        if (get().status !== 'guest') set({ user: null, status: 'guest' });
    },

    setUser(user) {
        set({ user });
    },
}));
