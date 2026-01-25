import { test, expect } from '@playwright/test'
import { clearAuthState, login, createTrip, navigateToTripDetails, createTask } from './helpers'

test('login -> create trip -> create task -> toggle status', async ({ page }) => {
  await clearAuthState(page)
  
  const tripTitle = `Trip ${Date.now()}`
  const taskTitle = `Task ${Date.now()}`

  await login(page)
  const tripId = await createTrip(page, tripTitle)
  
  await navigateToTripDetails(page, tripTitle)
  await createTask(page, taskTitle)

  // Ensure we're on trip details
  if (!page.url().includes(`/trips/${tripId}`)) {
    await page.goto(`/trips/${tripId}`)
    await expect(page.getByTestId('trip-details-title')).toBeVisible({ timeout: 15000 })
  }

  // Find the task
  const taskItem = page.locator('[data-testid^="trip-task-item-"]').filter({ hasText: taskTitle })
  await expect(taskItem).toBeVisible({ timeout: 10000 })

  const testId = await taskItem.getAttribute('data-testid')
  const taskId = testId?.replace('trip-task-item-', '')

  if (!taskId) {
    throw new Error('Missing task id in data-testid')
  }

  // Toggle completed - use fresh locators each time (DOM may refresh after save)
  const getCompleted = () => page.getByTestId(`task-completed-${taskId}`)
  const getSave = () => page.getByTestId(`task-save-${taskId}`)

  // Check the completed checkbox
  await getCompleted().check()
  await expect(getCompleted()).toBeChecked()

  // Set up response listener BEFORE clicking save
  const updateResponsePromise = page.waitForResponse((response) => {
    const url = response.url()
    return response.request().method() === 'PUT' &&
      (url.includes(`/api/tasks/update/${taskId}`) || url.includes(`/api/tasks/${taskId}`))
  })

  // Click save to trigger the PUT request
  const saveBtn = getSave()
  await expect(saveBtn).toBeEnabled({ timeout: 5000 })
  await saveBtn.click()

  const updateResponse = await updateResponsePromise
  expect([200, 204]).toContain(updateResponse.status())
})
