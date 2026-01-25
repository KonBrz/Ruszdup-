import { test, expect } from '@playwright/test'
import { clearAuthState, login } from './helpers'

test('login -> ai advice -> send message', async ({ page }) => {
  await clearAuthState(page)
  await login(page)

  await page.goto('/ai-advice')
  await expect(page.getByTestId('ai-messages')).toBeVisible({ timeout: 15000 })

  await page.getByTestId('ai-input').fill('Jaka jest prognoza?')
  
  const responsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/ai') && response.request().method() === 'POST'
  )
  
  await page.getByTestId('ai-submit').click()
  await responsePromise

  await expect(page.getByTestId('ai-message-assistant')).toHaveCount(2, { timeout: 15000 })
})
