import { test, expect } from '@playwright/test'

// Admin panel runs on backend (port 8000), not frontend
const BACKEND_URL = 'http://localhost:8000'

test.describe('Admin Panel Access', () => {
  test('guest visiting /admin redirects to /admin/login', async ({ page }) => {
    await page.goto(`${BACKEND_URL}/admin`)
    
    await expect(page).toHaveURL(/\/admin\/login/, { timeout: 15000 })
  })

  test('guest can view admin login page', async ({ page }) => {
    await page.goto(`${BACKEND_URL}/admin/login`)
    
    await expect(page).toHaveURL(/\/admin\/login/)
    // Filament login page should have email input
    await expect(page.locator('input[type="email"], input[id*="email"]').first()).toBeVisible({ timeout: 15000 })
  })

  test('guest visiting /admin/users redirects to /admin/login', async ({ page }) => {
    await page.goto(`${BACKEND_URL}/admin/users`)
    
    await expect(page).toHaveURL(/\/admin\/login/, { timeout: 15000 })
  })

  test('guest visiting /admin/flagged redirects to /admin/login', async ({ page }) => {
    await page.goto(`${BACKEND_URL}/admin/flagged`)
    
    await expect(page).toHaveURL(/\/admin\/login/, { timeout: 15000 })
  })
})
