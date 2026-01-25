import '@testing-library/jest-dom/vitest'
import { server } from '@/mocks/server'
import { vi } from 'vitest'
import { config } from '@vue/test-utils'
import { h } from 'vue'

// Stub router-link and router-view globally to avoid "Failed to resolve component" warnings
config.global.stubs = {
  'router-link': {
    template: '<a :href="to"><slot /></a>',
    props: ['to'],
  },
  'router-view': {
    template: '<div data-testid="router-view-stub"><slot /></div>',
  },
}

// Stub Teleport to prevent issues with teleported content in tests
config.global.stubs['Teleport'] = {
  render() {
    return h('div', { 'data-testid': 'teleport-stub' }, this.$slots.default?.())
  },
}

vi.mock('granim', () => ({
  default: class {
    destroy() {}
    pause() {}
  },
}))

if (!HTMLCanvasElement.prototype.getContext) {
  HTMLCanvasElement.prototype.getContext = () => null
}

// Mock window.location to prevent "Not implemented: navigation" errors
const locationDescriptor: PropertyDescriptor = {
  configurable: true,
  enumerable: true,
  get() {
    return {
      href: 'http://localhost:3000/',
      origin: 'http://localhost:3000',
      protocol: 'http:',
      host: 'localhost:3000',
      hostname: 'localhost',
      port: '3000',
      pathname: '/',
      search: '',
      hash: '',
      assign: vi.fn(),
      replace: vi.fn(),
      reload: vi.fn(),
      toString: () => 'http://localhost:3000/',
    }
  },
  set: vi.fn(),
}

Object.defineProperty(window, 'location', locationDescriptor)

// Suppress known jsdom/Vue warnings in tests
const originalError = console.error
const originalWarn = console.warn

console.error = (...args: unknown[]) => {
  const msg = args[0]
  if (typeof msg === 'string' && msg.includes('Not implemented: navigation')) {
    return // suppress jsdom navigation warning
  }
  originalError.apply(console, args)
}

console.warn = (...args: unknown[]) => {
  const msg = args[0]
  if (typeof msg === 'string' && msg.includes('Discarded invalid param(s)')) {
    return // suppress Vue Router param warning in tests
  }
  originalWarn.apply(console, args)
}

beforeAll(() => {
  server.listen({ onUnhandledRequest: 'warn' })
})

afterEach(() => {
  server.resetHandlers()
  vi.clearAllMocks()
})

afterAll(() => {
  server.close()
})
