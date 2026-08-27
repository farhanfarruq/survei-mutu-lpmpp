import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'

const priorityRoutes = ['/login', '/register']

for (const path of priorityRoutes) {
  test(`${path} has no detectable WCAG A/AA violations`, async ({ page }) => {
    await page.route('**/api/v1/me', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/problem+json',
        body: JSON.stringify({ code: 'unauthenticated', detail: 'Sesi tidak tersedia.' }),
      }),
    )
    await page.route('**/api/v1/auth/registration-options', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: { programs: [] } }),
      }),
    )
    await page.goto(path)
    await expect(page.locator('main')).toHaveCount(1)

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze()

    expect(results.violations).toEqual([])
  })
}

test('keyboard skip link, visible focus, zoom, and mobile reflow remain usable', async ({ page }) => {
  await page.route('**/api/v1/me', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: { id: 'keyboard-user', name: 'Pengguna Uji', email: 'keyboard@example.test', is_active: true, roles: ['respondent'], permissions: [], organizational_units: [] } }),
    }),
  )
  await page.route('**/api/v1/surveys/eligible', (route) =>
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [] }) }),
  )
  await page.goto('/app')
  const skipLink = page.getByRole('link', { name: 'Lewati ke konten utama' })
  await skipLink.focus()
  await expect(skipLink).toBeFocused()
  await skipLink.press('Enter')
  await expect(page.locator('#foundation-main')).toBeFocused()

  await page.setViewportSize({ width: 640, height: 800 })
  await page.evaluate(() => {
    document.documentElement.style.zoom = '2'
  })
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  expect(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)).toBe(false)
})

test('authenticated sidebar has readable contrast and a distinct active state', async ({ page }) => {
  await page.route('**/api/v1/me', (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: {
          id: 'accessibility-user',
          name: 'Pengguna Uji',
          email: 'accessibility@example.test',
          is_active: true,
          roles: ['respondent'],
          permissions: [],
          organizational_units: [],
        },
      }),
    }),
  )
  await page.route('**/api/v1/surveys/eligible', (route) =>
    route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ data: [] }) }),
  )

  await page.goto('/app')

  const sidebar = page.locator('.foundation-sidebar')
  const activeLink = sidebar.getByRole('link', { name: 'Beranda' })
  await expect(sidebar).toHaveCSS('background-color', 'rgb(18, 59, 90)')
  await expect(activeLink).toHaveCSS('color', 'rgb(255, 255, 255)')
  await expect(activeLink).toHaveCSS('background-color', 'rgb(7, 94, 140)')

  const results = await new AxeBuilder({ page })
    .include('.foundation-sidebar')
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze()

  expect(results.violations).toEqual([])

  await page.setViewportSize({ width: 390, height: 844 })
  await page.getByRole('button', { name: 'Buka navigasi' }).click()
  await expect(page.locator('.mobile-drawer')).toBeVisible()
  await expect(page.locator('.mobile-drawer').getByRole('link', { name: 'Beranda' })).toBeVisible()
})
