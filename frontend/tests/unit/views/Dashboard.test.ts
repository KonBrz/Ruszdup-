import { render } from '@testing-library/vue'
import { createPinia, setActivePinia } from 'pinia'
import Dashboard from '@/views/Dashboard.vue'
import { useAuthStore } from '@/stores/auth'

describe('Dashboard view', () => {
  it('shows user info when store is populated', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'Test User' }

    const { getByTestId, getByText } = render(Dashboard, {
      global: {
        plugins: [pinia],
      },
    })

    expect(getByTestId('dashboard-loaded')).toBeInTheDocument()
    expect(getByText(/test@example.com/i)).toBeInTheDocument()
  })
})
