import { defineStore } from 'pinia';
import apiClient from '@/api/axios'; 
import { ref, computed } from 'vue';

type ApiUser = {
    id: number;
    email: string;
    name?: string;
    is_admin?: boolean;
};

function isApiUser(data: unknown): data is ApiUser {
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
        return false;
    }

    const candidate = data as Record<string, unknown>;
    return (
        typeof candidate.id === 'number' &&
        typeof candidate.email === 'string' &&
        (candidate.name === undefined || typeof candidate.name === 'string')
    );
}
export const useAuthStore = defineStore('auth', () => {
    const user = ref<object | null>(null);
    const isAuthenticated = computed(() => !!user.value);
    const inviteToken = ref<string | null>(null);

    async function fetchUser() {
        try {
            const { data } = await apiClient.get('/api/user');
            let parsedData: unknown = data;

            if (typeof parsedData === 'string') {
                try {
                    parsedData = JSON.parse(parsedData);
                } catch {
                    // Keep original string if parsing fails.
                }
            }

            if (!isApiUser(parsedData)) {
                user.value = null;
                return null;
            }

            user.value = parsedData;
            // jeśli admin - backend panel
            if (parsedData.is_admin) {
                window.location.href = 'http://localhost:8000/admin'
                return
            }
            // jeśli zwykły user - dashboard frontendowy
            return parsedData
        } catch (error: any) {
            user.value = null;
            // Jeśli błąd to 401/419 (brak autoryzacji), nie ma potrzeby go logować jako błąd aplikacji
            if (error.response?.status !== 401 && error.response?.status !== 419) {
                console.error('Failed to fetch user:', error);
            }
        }
    }
    async function login(credentials: Record<string, string>) {
        await apiClient.get('/sanctum/csrf-cookie');
        const params = new URLSearchParams();
        params.append('email', credentials.email);
        params.append('password', credentials.password);
        await apiClient.post('/login', params);
        await fetchUser();
    }

    async function register(details: Record<string, string>) {
        await apiClient.get('/sanctum/csrf-cookie');
        const params = new URLSearchParams(details);
        await apiClient.post('/register', params);
        await fetchUser();
    }
    async function logout() {
        try {
            await apiClient.post('/logout');
        } finally {
            user.value = null;
        }
    }

    return { user, isAuthenticated, fetchUser, login, register, logout ,inviteToken};
});
