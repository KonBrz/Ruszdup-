import { createRouter, createMemoryHistory } from 'vue-router'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

const createTestRouter = () => {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login/:token?', name: 'Login', component: { template: '<div />' }, meta: { requiresGuest: true } },
      { path: '/dashboard', name: 'Dashboard', component: { template: '<div />' }, meta: { requiresAuth: true } },
      { path: '/trips', name: 'Trips', component: { template: '<div />' }, meta: { requiresAuth: true } },
    ],
  })

  router.beforeEach(async (to, _from, next) => {
    const authStore = useAuthStore()

    if (authStore.user === null) {
      await authStore.fetchUser()
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return next({ name: 'Login' })
    }

    if (to.meta.requiresGuest && authStore.isAuthenticated) {
      if (to.name === 'Login' && to.params.token) {
        return next()
      }
      return next({ path: '/dashboard' })
    }

    next()
  })

  return router
}

describe('router guards', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('redirects to login when route требует auth', async () => {
    const router = createTestRouter()
    const authStore = useAuthStore()
    authStore.user = null
    vi.spyOn(authStore, 'fetchUser').mockResolvedValue(null)

    await router.push('/trips')
    await router.isReady()

    expect(router.currentRoute.value.name).toBe('Login')
  })

  it('redirects to dashboard when authenticated user hits guest route', async () => {
    const router = createTestRouter()
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'Test User' }

    await router.push('/login')
    await router.isReady()

    expect(router.currentRoute.value.path).toBe('/dashboard')
  })

  it('allows login route when invite token present', async () => {
    const router = createTestRouter()
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'Test User' }

    await router.push({ name: 'Login', params: { token: 'invite-token' } })
    await router.isReady()

    expect(router.currentRoute.value.name).toBe('Login')
  })
})
