import { test, expect } from '@playwright/test'
import { 
  clearAuthState, 
  login, 
  createTrip, 
  navigateToTripDetails, 
  generateInviteToken, 
  logout, 
  registerUser, 
  acceptInvite,
  createTask,
  openTripMenu
} from './helpers'

test('flag trip, task and user', async ({ page }) => {
  await clearAuthState(page)
  
  const tripTitle = `Trip ${Date.now()}`
  const taskTitle = `Task ${Date.now()}`
  const userEmail = `user-${Date.now()}@example.com`

  // User A (owner) creates trip with task and generates invite
  await login(page, 'test@example.com', 'password')
  await createTrip(page, tripTitle)
  
  await navigateToTripDetails(page, tripTitle)
  await createTask(page, taskTitle)

  // Auto-accept dialogs for flag prompts
  page.on('dialog', (dialog) => dialog.accept('Powod'))

  const inviteToken = await generateInviteToken(page)
  expect(inviteToken).toBeTruthy()

  await logout(page)

  // User B registers and joins trip
  await registerUser(page, userEmail, 'User B')
  await acceptInvite(page, inviteToken)
  await expect(page.getByTestId('trip-details-title')).toBeVisible()

  // User B flags the trip (non-owner can flag)
  await openTripMenu(page)
  await expect(page.getByTestId('trip-flag')).toBeVisible({ timeout: 5000 })
  
  const tripFlagResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/flagged') && response.request().method() === 'POST'
  )
  await page.getByTestId('trip-flag').click()
  const tripFlagResponse = await tripFlagResponsePromise
  expect(tripFlagResponse.status()).toBe(201)

  // Flag a task
  const taskItem = page.locator('[data-testid^="trip-task-item-"]').first()
  await expect(taskItem).toBeVisible({ timeout: 10000 })
  const taskTestId = await taskItem.getAttribute('data-testid')
  const taskId = taskTestId?.replace('trip-task-item-', '')

  await page.getByTestId(`task-menu-${taskId}`).click()
  await expect(page.getByTestId(`task-flag-${taskId}`)).toBeVisible({ timeout: 5000 })
  
  const taskFlagResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/flagged') && response.request().method() === 'POST'
  )
  await page.getByTestId(`task-flag-${taskId}`).click()
  const taskFlagResponse = await taskFlagResponsePromise
  expect(taskFlagResponse.status()).toBe(201)

  // Flag a user
  const userMenu = page.locator('[data-testid^="user-menu-"]').first()
  await expect(userMenu).toBeVisible({ timeout: 10000 })
  await userMenu.click()
  
  const userFlagButton = page.locator('[data-testid^="user-flag-"]').first()
  await expect(userFlagButton).toBeVisible({ timeout: 5000 })
  
  const userFlagResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/flagged') && response.request().method() === 'POST'
  )
  await userFlagButton.click()
  const userFlagResponse = await userFlagResponsePromise
  expect(userFlagResponse.status()).toBe(201)
})
