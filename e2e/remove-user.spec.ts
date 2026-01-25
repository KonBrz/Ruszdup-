import { test, expect } from '@playwright/test'
import { 
  clearAuthState, 
  login, 
  createTrip, 
  navigateToTripDetails, 
  generateInviteToken, 
  logout, 
  registerUser, 
  acceptInvite 
} from './helpers'

test('owner removes invited user from trip', async ({ page }) => {
  await clearAuthState(page)
  
  const tripTitle = `Trip ${Date.now()}`
  const userEmail = `user-${Date.now()}@example.com`

  // User A (owner) creates trip and generates invite
  await login(page, 'test@example.com', 'password')
  await createTrip(page, tripTitle)
  
  await navigateToTripDetails(page, tripTitle)
  
  page.on('dialog', (dialog) => dialog.accept())
  const inviteToken = await generateInviteToken(page)
  expect(inviteToken).toBeTruthy()

  await logout(page)

  // User B registers and joins trip
  await registerUser(page, userEmail, 'User B')
  await acceptInvite(page, inviteToken)
  await expect(page.getByTestId('trip-details-title')).toBeVisible()

  await logout(page)

  // User A logs back in and removes User B
  await login(page, 'test@example.com', 'password')
  await navigateToTripDetails(page, tripTitle)

  // Find the second user's menu (User B)
  const userMenuButtons = page.locator('[data-testid^="user-menu-"]')
  await expect(userMenuButtons.nth(1)).toBeVisible({ timeout: 10000 })
  await userMenuButtons.nth(1).click()

  // Click remove button (API endpoint is lowercase: /deleteuser/)
  const removeResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/trips/') && 
                  response.url().toLowerCase().includes('/deleteuser/') && 
                  response.request().method() === 'DELETE'
  )
  
  const removeButtons = page.locator('[data-testid^="user-remove-"]')
  await expect(removeButtons.first()).toBeVisible({ timeout: 5000 })
  await removeButtons.first().click()
  
  await removeResponsePromise

  await logout(page)

  // User B logs in and should not see the trip
  await login(page, userEmail, 'password')
  await page.goto('/trips')
  
  // Wait for trips page to load (either list or empty message)
  await expect(
    page.getByTestId('trips-list').or(page.getByTestId('trips-empty'))
  ).toBeVisible({ timeout: 15000 })

  await expect(page.getByText(tripTitle)).not.toBeVisible({ timeout: 5000 })
})
