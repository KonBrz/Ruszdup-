import { render, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import Trip from '@/views/Trip.vue'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/trips', name: 'Trips', component: Trip },
      { path: '/trips/:id', name: 'TripDetails', component: { template: '<div />' } },
      { path: '/trips/createtrip', name: 'CreateTrip', component: { template: '<div />' } },
    ],
  })

describe('Trip view', () => {
  it('renders list of trips', async () => {
    server.use(
      http.get('/api/trips', () => HttpResponse.json([
        { id: 1, title: 'Trip A', description: 'Desc', start_date: '2025-01-01', end_date: '2025-01-05', trip_users: [] },
      ])),
    )

    const router = createTestRouter()
    await router.push('/trips')
    await router.isReady()

    const { getByTestId, getByText } = render(Trip, {
      global: { plugins: [createPinia(), router] },
    })

    await waitFor(() => expect(getByTestId('trips-list')).toBeInTheDocument())
    expect(getByText('Trip A')).toBeInTheDocument()
  })

  it('shows empty state', async () => {
    server.use(http.get('/api/trips', () => HttpResponse.json([])))

    const router = createTestRouter()
    await router.push('/trips')
    await router.isReady()

    const { getByText } = render(Trip, {
      global: { plugins: [createPinia(), router] },
    })

    await waitFor(() => expect(getByText(/Brak wycieczek/i)).toBeInTheDocument())
  })

  it('shows error on api failure', async () => {
    server.use(http.get('/api/trips', () => HttpResponse.json({ message: 'fail' }, { status: 500 })))

    const router = createTestRouter()
    await router.push('/trips')
    await router.isReady()

    const { getByText } = render(Trip, {
      global: { plugins: [createPinia(), router] },
    })

    await waitFor(() => expect(getByText(/Nie udało się pobrać/i)).toBeInTheDocument())
  })
})
