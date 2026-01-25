import { test, expect } from '@playwright/test'
import { clearAuthState, login, createTrip, navigateToTripDetails, openTripMenu } from './helpers'

test('login -> create trip -> edit -> delete', async ({ page }) => {
  await clearAuthState(page)
  
  const tripTitle = `Trip ${Date.now()}`
  const updatedTitle = `${tripTitle} updated`

  await login(page)
  await createTrip(page, tripTitle)
  
  await navigateToTripDetails(page, tripTitle)

  // Edit trip
  await openTripMenu(page)
  await page.getByTestId('trip-edit-link').click()

  await expect(page.getByTestId('trip-edit-form')).toBeVisible({ timeout: 10000 })
  await page.getByTestId('trip-edit-title').fill(updatedTitle)
  
  const updateResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/trips/') && response.request().method() === 'PUT'
  )
  await page.getByTestId('trip-edit-submit').click()
  await updateResponsePromise

  await expect(page.getByTestId('trip-details-title')).toHaveText(updatedTitle, { timeout: 10000 })

  // Delete trip
  page.once('dialog', (dialog) => dialog.accept())
  await openTripMenu(page)
  
  const deleteResponsePromise = page.waitForResponse(
    (response) => response.url().includes('/api/trips/') && response.request().method() === 'DELETE'
  )
  await page.getByTestId('trip-delete-button').click()
  await deleteResponsePromise

  await expect(page).toHaveURL(/\/trips/, { timeout: 10000 })
})
