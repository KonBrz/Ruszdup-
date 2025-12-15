import {createRouter, createWebHistory} from 'vue-router'
import {useAuthStore} from '@/stores/auth.ts';
import HomeView from '../views/HomeView.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView
        },
        {
            path: '/login',
            name: 'Login',
            component: () => import('../views/Login.vue'),
            meta: {requiresGuest: true}
        },

        {
            path: '/register',
            name: 'Register',
            component: () => import('../views/Register.vue'),
            meta: {requiresGuest: true}
        },
        {
            path: '/trips',
            name: 'Trips',
            component: () => import('../views/Trip.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/trips/createtrip',
            name: 'CreateTrip',
            component: () => import('../views/CreateTrip.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/trips/tasks/createtask/:id',
            name: 'CreateTask',
            component: () => import('../views/CreateTask.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/trips/edittrip/:id',
            name: 'EditTrip',
            component: () => import('../views/EditTrip.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/trips/tasks/edittask/:id',
            name: 'EditTask',
            component: () => import('../views/EditTask.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/trips/:id',
            name: 'TripDetails',
            component: () => import('../views/TripDetails.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/tasks',
            name: 'Tasks',
            component: () => import('../views/Tasks.vue'),
            meta: {requiresAuth: true}
        },
        {
            path: '/ai-advice',
            name: 'AiAdvice',
            component: () => import('../views/AiAdvice.vue'),
        },
        {
            path: '/dashboard',
            component: () => import('@/views/Dashboard.vue'),
            meta: {requiresAuth: true}
        }
    ]
})

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    if (authStore.user === null) {
        await authStore.fetchUser();
    }

    // Ścieżki wymagające logowania
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next({ name: 'Login' });
    }

    // Ścieżki tylko dla guestów
    if (to.meta.requiresGuest && authStore.isAuthenticated) {
        // Jeśli to login z tokenem, pozwól wejść
        if (to.name === 'Login' && to.params.token) {
            return next();
        }
        // Inaczej zalogowany → dashboard
        return next({ path: '/dashboard' });
    }

    next();
});

export default router