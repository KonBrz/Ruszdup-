import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia } from 'pinia'
import Login from '@/views/Login.vue'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', name: 'Login', component: Login },
      { path: '/dashboard', name: 'Dashboard', component: { template: '<div />' } },
    ],
  })

describe('Login view', () => {
  it('submits login and redirects to dashboard', async () => {
    const router = createTestRouter()
    await router.push('/login')
    await router.isReady()

    const { getByTestId } = render(Login, {
      global: {
        plugins: [createPinia(), router],
      },
    })

    await fireEvent.update(getByTestId('login-email'), 'test@example.com')
    await fireEvent.update(getByTestId('login-password'), 'password')
    await fireEvent.click(getByTestId('login-submit'))

    await waitFor(() => {
      expect(router.currentRoute.value.path).toBe('/dashboard')
    })
  })
})
