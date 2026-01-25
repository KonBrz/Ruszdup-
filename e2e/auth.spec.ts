import { test, expect } from '@playwright/test'
import { clearAuthState, login, registerUser, logout } from './helpers'

test('register -> logout', async ({ page }) => {
  await clearAuthState(page)
  
  const uniqueEmail = `user-${Date.now()}@example.com`
  await registerUser(page, uniqueEmail, 'Tester')
  await logout(page)
})

test('login with bad credentials shows error', async ({ page }) => {
  await clearAuthState(page)
  
  await page.goto('/login')
  await expect(page.getByTestId('login-email')).toBeVisible({ timeout: 10000 })
  
  await page.getByTestId('login-email').fill('bad@example.com')
  await page.getByTestId('login-password').fill('wrong')
  
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/login') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('login-submit').click()
  
  const response = await responsePromise
  expect([401, 422]).toContain(response.status())
  
  await expect(page).toHaveURL(/\/login/)
  await expect(page.getByTestId('login-error')).toBeVisible({ timeout: 10000 })
})

test('protected route redirects to login', async ({ page }) => {
  // Ensure completely clean state - no cookies, no storage
  await clearAuthState(page)
  
  // Verify we're not authenticated by checking /api/user returns 401
  const userResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/user')
  )
  
  await page.goto('/trips')
  
  // Wait for either redirect to login OR user API call
  const userResponse = await userResponsePromise.catch(() => null)
  
  if (userResponse && userResponse.status() === 200) {
    // Somehow still authenticated - this shouldn't happen with clearAuthState
    // Force clear and retry
    await page.context().clearCookies()
    await page.goto('/trips')
  }
  
  await expect(page).toHaveURL(/\/login/, { timeout: 15000 })
})
