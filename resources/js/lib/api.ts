import axios from 'axios';

const API_BASE = (import.meta.env.VITE_API_BASE as string) || '/api';

export const api = axios.create({
    baseURL: API_BASE,
    headers: { Accept: 'application/json' },
});

// Jeton invité pour le panier (généré une fois, persisté).
function getCartToken(): string {
    let token = localStorage.getItem('cart_token');
    if (!token) {
        token = crypto.randomUUID();
        localStorage.setItem('cart_token', token);
    }
    return token;
}

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    config.headers['X-Cart-Token'] = getCartToken();
    return config;
});

api.interceptors.response.use(
    (res) => res,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
        }
        return Promise.reject(error);
    },
);

export function apiErrorMessage(error: unknown): string {
    if (axios.isAxiosError(error)) {
        return (
            error.response?.data?.message ||
            Object.values(error.response?.data?.errors ?? {})?.[0]?.[0] ||
            error.message
        );
    }
    return 'Une erreur est survenue.';
}
