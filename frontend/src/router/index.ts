import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth';
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
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'Register',
      component: () => import('../views/Register.vue'),
      meta: { requiresGuest: true }
    },
    {
      path: '/trips',
      name: 'Trips',
      component: () => import('../views/Trip.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/tasks',
      name: 'Tasks',
      component: () => import('../views/Tasks.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/ai-advice',
      name: 'AiAdvice',
      component: () => import('../views/AiAdvice.vue'),
    },
     {
      path: '/dashboard',
      component: () => import('@/views/Dashboard.vue'),
      meta: { requiresAuth: true } 
     }
  ]
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Jeśli stan użytkownika nie jest jeszcze załadowany, spróbuj go pobrać.
  // Robimy to tylko raz, jeśli `user` jest `null`.
  if (authStore.user === null) {
    await authStore.fetchUser();
  }

  // Sprawdź, czy ścieżka wymaga uwierzytelnienia
  if (to.meta.requiresAuth) {
    // Jeśli użytkownik nie jest zalogowany, przekieruj do logowania
    if (!authStore.isAuthenticated) {
      return next({ name: 'Login' });
    }
  }

  // Sprawdź, czy ścieżka jest tylko dla gości (niezalogowanych)
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    // Jeśli zalogowany użytkownik próbuje wejść na /login lub /register, przekieruj go do panelu
    return next({ path: '/dashboard' });
  }

  next();
});

export default router