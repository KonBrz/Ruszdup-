import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import CreateTask from '@/views/CreateTask.vue'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/trips/tasks/createtask/:id', name: 'CreateTask', component: CreateTask },
      { path: '/trips/:id', name: 'TripDetails', component: { template: '<div />' } },
    ],
  })

describe('CreateTask view', () => {
  it('creates task and redirects', async () => {
    server.use(
      http.get('/api/trips/1', () => HttpResponse.json({
        id: 1,
        trip_users: [{ id: 1, name: 'User' }],
      })),
      http.post('/api/tasks', () => HttpResponse.json({ id: 1 }, { status: 201 })),
    )

    const router = createTestRouter()
    await router.push('/trips/tasks/createtask/1')
    await router.isReady()

    const { getByTestId } = render(CreateTask, {
      global: { plugins: [createPinia(), router] },
    })

    await waitFor(() => expect(getByTestId('task-create-form')).toBeInTheDocument())
    await fireEvent.update(getByTestId('task-title'), 'Task A')
    await fireEvent.update(getByTestId('task-priority'), 'niski')
    await fireEvent.update(getByTestId('task-deadline'), '2025-02-10')
    await fireEvent.click(getByTestId('task-user-1'))
    await fireEvent.click(getByTestId('task-submit'))

    await waitFor(() => expect(router.currentRoute.value.fullPath).toBe('/trips/1'))
  })
})
