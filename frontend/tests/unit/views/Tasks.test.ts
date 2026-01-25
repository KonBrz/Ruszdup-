import { render, waitFor } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import Tasks from '@/views/Tasks.vue'

const createTestRouter = () =>
  createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/tasks', name: 'Tasks', component: Tasks },
      { path: '/trips/:id', name: 'TripDetails', component: { template: '<div />' } },
    ],
  })

describe('Tasks view', () => {
  it('renders task list', async () => {
    server.use(
      http.get('/api/tasks', () => HttpResponse.json([
        { id: 1, title: 'Task A', trip_id: 1, task_users: [] },
      ])),
    )

    const router = createTestRouter()
    await router.push('/tasks')
    await router.isReady()

    const { getByTestId, getByText } = render(Tasks, {
      global: { plugins: [createPinia(), router] },
    })

    await waitFor(() => expect(getByTestId('tasks-list')).toBeInTheDocument())
    expect(getByText('Task A')).toBeInTheDocument()
  })
})
