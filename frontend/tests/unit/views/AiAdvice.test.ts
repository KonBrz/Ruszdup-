import { render, fireEvent, waitFor } from '@testing-library/vue'
import { createPinia } from 'pinia'
import { server } from '@/mocks/server'
import { http, HttpResponse } from 'msw'
import AiAdvice from '@/views/AiAdvice.vue'

describe('AiAdvice view', () => {
  it('sends message and renders response', async () => {
    server.use(
      http.post('/api/ai-chat', () => HttpResponse.json({ response: 'OK' })),
    )

    const { getByTestId, getAllByTestId } = render(AiAdvice, {
      global: { plugins: [createPinia()] },
    })

    await fireEvent.update(getByTestId('ai-input'), 'Test')
    await fireEvent.click(getByTestId('ai-submit'))

    await waitFor(() => expect(getAllByTestId('ai-message-assistant').length).toBeGreaterThan(1))
  })
})
