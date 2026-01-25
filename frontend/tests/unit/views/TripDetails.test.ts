import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import TripDetails from '@/views/TripDetails.vue'
import { useAuthStore } from '@/stores/auth'
import { vi } from 'vitest'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/trips/:id', name: 'TripDetails', component: TripDetails },
      { path: '/trips/tasks/createtask/:id', name: 'CreateTask', component: { template: '<div />' } },
      { path: '/trips/edittrip/:id', name: 'EditTrip', component: { template: '<div />' } },
      { path: '/trips', name: 'Trips', component: { template: '<div />' } },
    ],
  })

const makeTripResponse = (overrides = {}) => ({
  id: 1,
  title: 'Trip A',
  description: 'Opis',
  destination: 'Gdansk',
  start_date: '2025-01-01',
  end_date: '2025-01-05',
  can_edit_trip: true,
  user: { id: 1, name: 'User' },
  trip_users: [{ id: 1, name: 'User' }],
  tasks: [
    {
      id: 10,
      title: 'Task A',
      priority: 'niski',
      deadline: null,
      can_edit_task: true,
      task_users: [
        { id: 1, name: 'User', pivot: { completed: 0, ignored: 0 } },
      ],
    },
  ],
  ...overrides,
})

describe('TripDetails view', () => {
  beforeEach(() => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }
  })

  it('renders trip details and tasks', async () => {
    server.use(
      http.get('/api/trips/1', () => HttpResponse.json(makeTripResponse())),
    )

    const router = createTestRouter()
    await router.push('/trips/1')
    await router.isReady()

    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }

    const { getByTestId } = render(TripDetails, {
      global: { plugins: [pinia, router] },
    })

    await waitFor(() => expect(getByTestId('trip-details-title')).toBeInTheDocument())
    expect(getByTestId('trip-tasks-list')).toBeInTheDocument()
  })

  it('updates task status via api', async () => {
    let updateCalled = false
    server.use(
      http.get('/api/trips/1', () => HttpResponse.json(makeTripResponse())),
      http.put('/api/tasks/update/10', () => {
        updateCalled = true
        return HttpResponse.json({})
      }),
    )
    vi.spyOn(window, 'alert').mockImplementation(() => {})

    const router = createTestRouter()
    await router.push('/trips/1')
    await router.isReady()

    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }

    const { getByTestId } = render(TripDetails, {
      global: { plugins: [pinia, router] },
    })

    await waitFor(() => expect(getByTestId('trip-details-title')).toBeInTheDocument())

    const saveButton = getByTestId('task-save-10')
    await fireEvent.click(saveButton)
    await waitFor(() => expect(updateCalled).toBe(true))
  })

  it('handles invite action', async () => {
    let inviteCalled = false
    vi.spyOn(window, 'alert').mockImplementation(() => {})
    Object.assign(navigator, { clipboard: { writeText: vi.fn() } })

    server.use(
      http.get('/api/trips/1', () => HttpResponse.json(makeTripResponse({ can_edit_trip: true }))),
      http.post('/api/trips/1/invite', () => {
        inviteCalled = true
        return HttpResponse.json({ link: '1?invite_token=token' })
      }),
    )

    const router = createTestRouter()
    await router.push('/trips/1')
    await router.isReady()

    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }

    const { getByTestId } = render(TripDetails, {
      global: { plugins: [pinia, router] },
    })

    await waitFor(() => expect(getByTestId('trip-details-title')).toBeInTheDocument())

    await fireEvent.click(getByTestId('trip-menu-button'))
    await fireEvent.click(getByTestId('trip-invite'))
    await waitFor(() => expect(inviteCalled).toBe(true))
  })

  it('handles flag action for non-owner', async () => {
    let flagCalled = false
    vi.spyOn(window, 'prompt').mockReturnValue('Reason')
    vi.spyOn(window, 'alert').mockImplementation(() => {})

    server.use(
      http.get('/api/trips/1', () => HttpResponse.json(makeTripResponse({ can_edit_trip: false }))),
      http.post('/api/flagged', () => {
        flagCalled = true
        return HttpResponse.json({ id: 1 }, { status: 201 })
      }),
    )

    const router = createTestRouter()
    await router.push('/trips/1')
    await router.isReady()

    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }

    const { getByTestId } = render(TripDetails, {
      global: { plugins: [pinia, router] },
    })

    await waitFor(() => expect(getByTestId('trip-details-title')).toBeInTheDocument())

    await fireEvent.click(getByTestId('trip-menu-button'))
    await fireEvent.click(getByTestId('trip-flag'))
    await waitFor(() => expect(flagCalled).toBe(true))
  })

  it('handles ai action', async () => {
    let aiCalled = false
    vi.spyOn(window, 'alert').mockImplementation(() => {})
    vi.spyOn(window, 'confirm').mockReturnValue(false)

    server.use(
      http.get('/api/trips/1', () => HttpResponse.json(makeTripResponse())),
      http.post('/api/ai-chat', () => {
        aiCalled = true
        return HttpResponse.json({ response: 'Nowe zadanie' })
      }),
    )

    const router = createTestRouter()
    await router.push('/trips/1')
    await router.isReady()

    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }

    const { getByText } = render(TripDetails, {
      global: { plugins: [pinia, router] },
    })

    await waitFor(() => expect(getByText(/Zapytaj AI/)).toBeInTheDocument())
    await fireEvent.click(getByText(/Zapytaj AI/))
    await waitFor(() => expect(aiCalled).toBe(true))
  })
})
