import { http, HttpResponse } from 'msw'

export const mockUser = {
  id: 1,
  email: 'test@example.com',
  name: 'Test User',
  is_admin: false,
}

export const handlers = [
  http.get('/sanctum/csrf-cookie', () => HttpResponse.text('', { status: 204 })),
  http.post('/login', () => HttpResponse.text('', { status: 204 })),
  http.post('/register', () => HttpResponse.text('', { status: 204 })),
  http.post('/logout', () => HttpResponse.text('', { status: 204 })),

  http.get('/api/user', () => HttpResponse.json(mockUser)),
  http.get('/api/trips', () => HttpResponse.json([])),
  http.get('/api/tasks', () => HttpResponse.json([])),

  http.post('/api/ai-chat', () => HttpResponse.json({ response: 'OK' })),
]
