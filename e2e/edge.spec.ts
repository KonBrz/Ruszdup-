import { test, expect } from '@playwright/test'
import { clearAuthState, login } from './helpers'

test('404 trip shows not found/unauthorized state', async ({ page }) => {
  await clearAuthState(page)
  await login(page, 'test@example.com', 'password')

  await page.goto('/trips/999999')
  await expect(page.getByText(/Nie udało się pobrać|Nie należysz/i)).toBeVisible({ timeout: 15000 })
})

test('session expired redirects to login', async ({ page }) => {
  await clearAuthState(page)
  await login(page, 'test@example.com', 'password')

  // Clear cookies to simulate expired session
  await page.context().clearCookies()
  
  // Navigate to protected route - should redirect to login
  await page.goto('/trips')
  await expect(page).toHaveURL(/\/login/, { timeout: 15000 })
})
