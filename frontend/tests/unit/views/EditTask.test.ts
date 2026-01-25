import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import EditTask from '@/views/EditTask.vue'
import { useAuthStore } from '@/stores/auth'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/trips/tasks/edittask/:id', name: 'EditTask', component: EditTask },
      { path: '/trips/:id', name: 'TripDetails', component: { template: '<div />' } },
    ],
  })

describe('EditTask view', () => {
  it('loads task and submits updates', async () => {
    server.use(
      http.get('/api/tasks/1', () => HttpResponse.json({
        id: 1,
        title: 'Task A',
        priority: 'niski',
        deadline: '2025-02-10',
        trip_id: 1,
        task_users: [{ id: 1, name: 'User', pivot: { completed: 0, ignored: 0 } }],
        trip: { id: 1, trip_users: [{ id: 1, name: 'User' }] },
      })),
      http.put('/api/tasks/1', () => HttpResponse.json({ id: 1 })),
    )

    const pinia = createPinia()
    setActivePinia(pinia)
    const authStore = useAuthStore()
    authStore.user = { id: 1, email: 'test@example.com', name: 'User' }

    const router = createTestRouter()
    await router.push('/trips/tasks/edittask/1')
    await router.isReady()

    const { getByTestId } = render(EditTask, {
      global: { plugins: [pinia, router] },
    })

    await waitFor(() => expect(getByTestId('task-edit-form')).toBeInTheDocument())
    await fireEvent.update(getByTestId('task-edit-title'), 'Task Updated')
    await fireEvent.update(getByTestId('task-edit-priority'), 'średni')
    await fireEvent.click(getByTestId('task-edit-submit'))

    await waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/trips/1'))
  })
})
