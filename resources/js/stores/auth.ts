import { create } from 'zustand';
import { api } from '@/lib/api';
import type { User } from '@/types';

interface AuthState {
    user: User | null;
    token: string | null;
    initialized: boolean;
    setSession: (user: User, token: string) => void;
    loadProfile: () => Promise<void>;
    logout: () => Promise<void>;
    isSeller: () => boolean;
}

export const useAuth = create<AuthState>((set, get) => ({
    user: null,
    token: localStorage.getItem('auth_token'),
    initialized: false,

    setSession: (user, token) => {
        localStorage.setItem('auth_token', token);
        set({ user, token });
    },

    loadProfile: async () => {
        const token = localStorage.getItem('auth_token');
        if (!token) {
            set({ initialized: true });
            return;
        }
        try {
            const { data } = await api.get<User>('/user/profile');
            set({ user: data, token, initialized: true });
        } catch {
            localStorage.removeItem('auth_token');
            set({ user: null, token: null, initialized: true });
        }
    },

    logout: async () => {
        try {
            await api.post('/auth/logout');
        } catch {
            // on ignore : on nettoie quand même la session locale
        }
        localStorage.removeItem('auth_token');
        set({ user: null, token: null });
    },

    isSeller: () => get().user?.user_type === 'seller',
}));
