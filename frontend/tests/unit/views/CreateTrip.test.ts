import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import CreateTrip from '@/views/CreateTrip.vue'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/trips/createtrip', name: 'CreateTrip', component: CreateTrip },
      { path: '/trips', name: 'Trips', component: { template: '<div />' } },
    ],
  })

describe('CreateTrip view', () => {
  it('submits form and shows success', async () => {
    server.use(
      http.post('/api/trips', () => HttpResponse.json({ id: 1 }, { status: 201 })),
    )

    const router = createTestRouter()
    await router.push('/trips/createtrip')
    await router.isReady()

    const { getByTestId } = render(CreateTrip, {
      global: { plugins: [createPinia(), router] },
    })

    await fireEvent.update(getByTestId('trip-title'), 'Trip A')
    await fireEvent.update(getByTestId('trip-destination'), 'Gdansk')
    await fireEvent.update(getByTestId('trip-description'), 'Opis')
    await fireEvent.update(getByTestId('trip-start-date'), '2025-01-01')
    await fireEvent.update(getByTestId('trip-end-date'), '2025-01-05')
    await fireEvent.click(getByTestId('trip-submit'))

    await waitFor(() => expect(getByTestId('trip-create-success')).toBeInTheDocument())
  })

  it('shows error when api fails', async () => {
    server.use(
      http.post('/api/trips', () => HttpResponse.json({ message: 'fail' }, { status: 500 })),
    )

    const router = createTestRouter()
    await router.push('/trips/createtrip')
    await router.isReady()

    const { getByTestId } = render(CreateTrip, {
      global: { plugins: [createPinia(), router] },
    })

    await fireEvent.update(getByTestId('trip-title'), 'Trip A')
    await fireEvent.update(getByTestId('trip-destination'), 'Gdansk')
    await fireEvent.update(getByTestId('trip-start-date'), '2025-01-01')
    await fireEvent.update(getByTestId('trip-end-date'), '2025-01-05')
    await fireEvent.click(getByTestId('trip-submit'))

    await waitFor(() => expect(getByTestId('trip-create-error')).toBeInTheDocument())
  })
})
