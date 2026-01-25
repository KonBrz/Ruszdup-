import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: 'e2e',
  timeout: 60000,
  expect: {
    timeout: 15000,
  },
  fullyParallel: true,
  retries: 1,
  workers: process.env.CI ? 2 : undefined,
  use: {
    baseURL: 'http://localhost:5173',
    trace: 'on-first-retry',
    // Each test gets a fresh browser context (no shared state)
    storageState: undefined,
    // Increase action timeout for slower CI
    actionTimeout: 10000,
  },
})
