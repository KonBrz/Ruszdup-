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

test('invite flow adds second user to trip', async ({ page }) => {
  await clearAuthState(page)
  
  const tripTitle = `Trip ${Date.now()}`
  const userEmail = `user-${Date.now()}@example.com`

  // User A creates trip and generates invite
  await login(page, 'test@example.com', 'password')
  const tripId = await createTrip(page, tripTitle)
  
  await navigateToTripDetails(page, tripTitle)
  
  page.on('dialog', (dialog) => dialog.accept())
  const inviteToken = await generateInviteToken(page)
  expect(inviteToken).toBeTruthy()

  await logout(page)

  // User B registers and joins trip
  await registerUser(page, userEmail, 'User B')
  await acceptInvite(page, inviteToken)

  // Verify we're on the trip details page
  await expect(page).toHaveURL(new RegExp(`/trips/${tripId}`), { timeout: 10000 })
  await expect(page.getByTestId('trip-details-title')).toBeVisible()
})
