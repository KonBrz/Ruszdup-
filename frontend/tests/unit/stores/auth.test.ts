import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'

const mockUser = {
  id: 1,
  email: 'test@example.com',
  name: 'Test User',
  is_admin: false,
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('login fetches csrf, logs in, and sets user', async () => {
    const store = useAuthStore()

    await store.login({ email: 'test@example.com', password: 'password' })

    expect(store.user).toMatchObject({ email: 'test@example.com' })
    expect(store.isAuthenticated).toBe(true)
  })

  it('logout clears user state', async () => {
    const store = useAuthStore()
    store.user = mockUser

    await store.logout()

    expect(store.user).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('fetchUser sets user when authenticated', async () => {
    const store = useAuthStore()

    await store.fetchUser()

    expect(store.user).toMatchObject({ email: 'test@example.com' })
  })

  it('fetchUser handles 401 by clearing user', async () => {
    server.use(
      http.get('/api/user', () => HttpResponse.json({ message: 'Unauthenticated' }, { status: 401 })),
    )
    const store = useAuthStore()
    store.user = mockUser

    await store.fetchUser()

    expect(store.user).toBeNull()
  })

  it('login surfaces 422 errors to caller', async () => {
    server.use(
      http.post('/login', () =>
        HttpResponse.json({ errors: { email: ['Invalid'] } }, { status: 422 }),
      ),
    )
    const store = useAuthStore()

    await expect(store.login({ email: 'bad@example.com', password: 'bad' })).rejects.toBeTruthy()
    expect(store.user).toBeNull()
  })
})
