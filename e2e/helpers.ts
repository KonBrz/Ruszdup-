import { expect, type Page } from '@playwright/test'

/**
 * Clear all auth state before test - ensures clean slate
 */
export async function clearAuthState(page: Page) {
  await page.context().clearCookies()
  await page.addInitScript(() => {
    localStorage.clear()
    sessionStorage.clear()
  })
}

/**
 * Robust login helper that waits for proper responses
 */
export async function login(page: Page, email: string = 'test@example.com', password: string = 'password') {
  // Clear state first
  await clearAuthState(page)
  
  await page.goto('/login')
  await expect(page.getByTestId('login-email')).toBeVisible({ timeout: 10000 })
  
  await page.getByTestId('login-email').fill(email)
  await page.getByTestId('login-password').fill(password)
  
  // Wait for login response
  const loginResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/login') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('login-submit').click()
  
  const loginResponse = await loginResponsePromise
  const status = loginResponse.status()
  
  // Handle success (204/200/302) vs failure (401/422)
  if (status === 401 || status === 422) {
    throw new Error(`Login failed with status ${status}`)
  }
  
  // Wait for user fetch or dashboard to confirm auth
  await Promise.race([
    page.waitForResponse((r) => r.url().includes('/api/user') && r.status() === 200),
    expect(page.getByTestId('dashboard-loaded')).toBeVisible({ timeout: 15000 }),
  ])
}

/**
 * Create a new trip and return the trip ID
 */
export async function createTrip(page: Page, title: string): Promise<number> {
  await page.goto('/trips/createtrip')
  await expect(page.getByTestId('trip-title')).toBeVisible({ timeout: 10000 })
  
  await page.getByTestId('trip-title').fill(title)
  await page.getByTestId('trip-destination').fill('Gdansk')
  await page.getByTestId('trip-description').fill('Opis testowy')
  await page.getByTestId('trip-start-date').fill('2025-01-01')
  await page.getByTestId('trip-end-date').fill('2025-01-05')
  
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/trips') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('trip-submit').click()
  
  const response = await responsePromise
  expect(response.status()).toBe(201)
  
  await expect(page.getByTestId('trip-create-success')).toBeVisible({ timeout: 10000 })
  
  const body = await response.json()
  return body.id as number
}

/**
 * Navigate to trip details by clicking trip item - waits for navigation
 */
export async function navigateToTripDetails(page: Page, tripTitle: string) {
  await page.goto('/trips')
  await expect(page.getByTestId('trips-list')).toBeVisible({ timeout: 15000 })
  
  const tripItem = page.locator('[data-testid^="trip-item-"]').filter({ hasText: tripTitle })
  await expect(tripItem.first()).toBeVisible({ timeout: 10000 })
  
  // Wait for navigation to trip details
  const navigationPromise = page.waitForURL(/\/trips\/\d+/)
  await tripItem.first().click()
  await navigationPromise
  
  await expect(page.getByTestId('trip-details-title')).toBeVisible({ timeout: 15000 })
}

/**
 * Open trip menu dropdown and wait for it to be visible
 */
export async function openTripMenu(page: Page) {
  await page.getByTestId('trip-menu-button').click()
  await expect(page.getByTestId('trip-menu-dropdown')).toBeVisible({ timeout: 10000 })
}

/**
 * Generate invite link and return the token
 */
export async function generateInviteToken(page: Page): Promise<string> {
  await openTripMenu(page)
  await expect(page.getByTestId('trip-invite')).toBeVisible({ timeout: 5000 })
  
  const inviteResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/invite') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('trip-invite').click()
  await inviteResponsePromise
  
  // Expand details to see token
  await page.getByRole('button', { name: 'Rozwiń szczegóły' }).click()
  
  // Get token from data-testid element
  const tokenElement = page.getByTestId('trip-invite-token')
  await expect(tokenElement).toBeVisible({ timeout: 10000 })
  const token = await tokenElement.textContent()
  
  if (!token) {
    throw new Error('Failed to get invite token')
  }
  
  return token.trim()
}

/**
 * Register a new user with unique email
 */
export async function registerUser(page: Page, email: string, name: string = 'Test User') {
  await page.goto('/register')
  await expect(page.getByTestId('email')).toBeVisible({ timeout: 10000 })
  
  await page.getByTestId('username').fill(name)
  await page.getByTestId('email').fill(email)
  await page.getByTestId('password').fill('password')
  await page.getByTestId('password_confirmation').fill('password')
  
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/register') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('register-submit').click()
  await responsePromise
  
  await expect(page.getByTestId('dashboard-loaded')).toBeVisible({ timeout: 15000 })
}

/**
 * Logout and verify redirect to login
 */
export async function logout(page: Page) {
  await page.getByTestId('nav-logout').click()
  await expect(page).toHaveURL(/\/login/, { timeout: 10000 })
}

/**
 * Accept trip invite using token
 */
export async function acceptInvite(page: Page, token: string) {
  await page.goto('/trips')
  await expect(page.getByRole('button', { name: 'Dołącz do wycieczki' })).toBeVisible({ timeout: 10000 })
  
  await page.getByRole('button', { name: 'Dołącz do wycieczki' }).click()
  await page.getByTestId('invite-token-input').fill(token)
  
  const acceptResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/trip-invite/accept') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('invite-token-submit').click()
  
  const acceptResponse = await acceptResponsePromise
  expect(acceptResponse.status()).toBe(200)
  
  await expect(page.getByTestId('trip-details-title')).toBeVisible({ timeout: 15000 })
}

/**
 * Create a task within current trip details page
 */
export async function createTask(page: Page, title: string) {
  await page.getByTestId('trip-add-task').click()
  await expect(page.getByTestId('task-create-form')).toBeVisible({ timeout: 10000 })
  
  await page.getByTestId('task-title').fill(title)
  await page.getByTestId('task-priority').selectOption('niski')
  
  const assignee = page.locator('input[type="checkbox"][data-testid^="task-user-"]').first()
  await assignee.check()
  
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/tasks') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('task-submit').click()
  
  const response = await responsePromise
  expect(response.status()).toBe(201)
  
  await expect(page.getByTestId('trip-details-title')).toBeVisible({ timeout: 10000 })
}
