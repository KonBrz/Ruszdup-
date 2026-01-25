import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia } from 'pinia'
import Register from '@/views/Register.vue'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/register', name: 'Register', component: Register },
      { path: '/dashboard', name: 'Dashboard', component: { template: '<div />' } },
    ],
  })

describe('Register view', () => {
  it('submits registration and redirects to dashboard', async () => {
    const router = createTestRouter()
    await router.push('/register')
    await router.isReady()

    const { getByTestId } = render(Register, {
      global: {
        plugins: [createPinia(), router],
      },
    })

    await fireEvent.update(getByTestId('username'), 'Tester')
    await fireEvent.update(getByTestId('email'), 'test@example.com')
    await fireEvent.update(getByTestId('password'), 'password')
    await fireEvent.update(getByTestId('password_confirmation'), 'password')
    await fireEvent.click(getByTestId('register-submit'))

    await waitFor(() => {
      expect(router.currentRoute.value.path).toBe('/dashboard')
    })
  })
})
