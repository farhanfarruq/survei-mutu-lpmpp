import { expect, test } from '@playwright/test'

test('respondent can validate, autosave, and submit the mock survey', async ({ page }) => {
  await page.goto('/respondent')
  await expect(page.getByRole('heading', { level: 1 })).toContainText('Alya')
  await page.getByRole('button', { name: 'Lanjutkan pengisian' }).click()
  await page.getByRole('button', { name: 'Tinjau dan kirim' }).click()
  await expect(page.getByRole('alert')).toContainText('3 jawaban wajib')

  for (const code of ['LA-01', 'LA-02', 'LA-03']) {
    await page.getByRole('radio', { name: '4 Sangat baik', exact: true }).nth(code === 'LA-01' ? 0 : code === 'LA-02' ? 1 : 2).check()
  }
  await expect(page.getByText(/Tersimpan di tab ini/)).toBeVisible()
  await page.getByRole('button', { name: 'Tinjau dan kirim' }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('button', { name: 'Ya, kirim simulasi' }).click()
  await expect(page.getByText('SIM-2026-00428')).toBeVisible()
})

test('leadership scope changes aggregate fixtures and exposes no raw response', async ({ page }) => {
  await page.goto('/leadership')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Dashboard Pimpinan')
  await page.getByLabel('Unit dalam scope').selectOption('engineering')
  await expect(page.getByText('84,1')).toBeVisible()
  await expect(page.getByText(/raw response/i)).toHaveCount(0)
})

test('priority routes have landmarks, labels, and reflow at mobile width', async ({ page }) => {
  await page.route('http://localhost:8000/api/v1/me', async (route) => {
    await route.fulfill({ status: 401, contentType: 'application/problem+json', body: JSON.stringify({ detail: 'Sesi tidak tersedia.', code: 'unauthenticated' }) })
  })
  await page.setViewportSize({ width: 320, height: 800 })
  for (const path of ['/login', '/respondent', '/surveys', '/admin', '/builder', '/results', '/leadership', '/ai-analysis', '/ai-config', '/follow-up', '/reports']) {
    await page.goto(path)
    await expect(page.locator('main')).toHaveCount(1)
    await expect(page.getByRole('heading', { level: 1 })).toHaveCount(1)
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)
    expect(overflow, `${path} must reflow at 320px`).toBe(false)
  }
})

test('AI and secret configuration remain visibly simulated', async ({ page }) => {
  await page.goto('/ai-analysis')
  await expect(page.getByText(/SIMULASI AI · POST-MVP/)).toBeVisible()
  await page.getByRole('button', { name: 'Jalankan simulasi' }).click()
  await expect(page.getByText('Draft membutuhkan review')).toBeVisible()

  await page.goto('/ai-config')
  await expect(page.getByLabel('Secret tersimpan')).toHaveValue('••••••••••••7A9C')
  await expect(page.getByText('Custom Base URL')).toBeVisible()
  await page.getByRole('button', { name: 'Ganti secret mock' }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
})

test('external respondent completes the production response flow at mobile width', async ({ page }) => {
  let version = 1
  const survey = {
    id: 'survey-1', code: 'SRV-MVP', name: 'Survei Mutu Layanan', privacy_mode: 'anonymous',
    privacy_notice: 'Isi jawaban dipisahkan dari data partisipasi.', closes_at: '2026-12-31T23:59:59+07:00',
    question_count: 1, estimated_minutes: 1, participation_status: 'eligible',
    sections: [{ id: 'section-1', code: 'SEC', title: 'Layanan', description: 'Nilai pengalaman Anda.', position: 1, questions: [{ id: 'question-1', code: 'Q1', text: 'Bagaimana mutu layanan?', response_type: 'scale', required: true, validation: null, options: [{ value: '4', label: 'Sangat baik' }], na_allowed: false }] }],
  }

  await page.route('http://localhost:8000/api/v1/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    const method = route.request().method()
    const data = path === '/api/v1/respondent-sessions'
      ? { session_token: 'session-token', completion_token: 'completion-token', expires_at: survey.closes_at }
      : path === '/api/v1/respondent-survey'
        ? survey
        : path === '/api/v1/responses' && method === 'POST'
          ? { id: 'response-1', state: 'started', version, progress: 0, survey, answers: [], receipt: null }
          : path === '/api/v1/responses/response-1' && method === 'GET'
            ? { id: 'response-1', state: 'started', version, progress: 0, survey, answers: [], receipt: null }
            : path === '/api/v1/responses/response-1' && method === 'PATCH'
              ? { id: 'response-1', state: 'partial', version: ++version, progress: 100, survey, answers: [{ question_id: 'question-1', value: '4' }], receipt: null }
              : { receipt_code: 'SM-202608-TEST123456', submitted_at: '2026-08-08T08:00:00+07:00', response_id: 'response-1' }
    await route.fulfill({ status: path.endsWith('/submissions') ? 200 : path === '/api/v1/responses' || path === '/api/v1/respondent-sessions' ? 201 : 200, contentType: 'application/json', body: JSON.stringify({ data }) })
  })

  await page.setViewportSize({ width: 320, height: 800 })
  await page.goto('/invitations/external-token')
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Survei Mutu Layanan')
  await page.getByRole('checkbox', { name: /bersedia berpartisipasi/ }).check()
  await page.getByRole('button', { name: 'Setuju dan mulai' }).click()
  await page.getByRole('radio', { name: 'Sangat baik' }).check()
  await page.getByRole('button', { name: 'Tinjau dan kirim' }).click()
  await expect(page.getByRole('dialog')).toBeVisible()
  await page.getByRole('button', { name: 'Ya, kirim respons' }).click()
  await expect(page.getByText('SM-202608-TEST123456')).toBeVisible()
  expect(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth)).toBe(false)
})

test('Phase 13 production workspaces are permission-aware and reflow on mobile', async ({ page }) => {
  await page.route('http://localhost:8000/api/v1/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    const data = path === '/api/v1/me'
      ? { id: 'user-public', name: 'Analyst Fiktif', email: 'analyst@example.test', is_active: true, roles: ['analyst'], permissions: ['ai.read', 'ai.execute', 'notification.read', 'finding.read', 'follow-up.dashboard.read'], organizational_units: [{ id: 'unit-1', code: 'UNIT', name: 'Unit Fiktif', scope_mode: 'self', is_primary: true }] }
      : path === '/api/v1/ai-provider-configs' || path === '/api/v1/ai-prompt-templates' || path === '/api/v1/findings'
        ? []
        : path === '/api/v1/notifications'
          ? [{ id: 'notification-1', type: 'ai_failure', title: 'AI memakai fallback', message: 'Gunakan statistik deterministik.', route: '/app/analytics', context: {}, read_at: null, created_at: '2026-08-08T08:00:00+07:00' }]
          : { counts: {}, total: 0, overdue: 0, pending_verification: 0, revision: 0, items: [] }
    await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify(path === '/api/v1/notifications' ? { data, meta: { unread: 1 } } : { data }) })
  })

  await page.setViewportSize({ width: 320, height: 800 })
  for (const [path, heading] of [['/app/ai', 'AI Analysis'], ['/app/notifications', 'Notifikasi'], ['/app/follow-up', 'Tindak Lanjut']] as const) {
    await page.goto(path)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading)
    expect(await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth), `${path} must reflow at 320px`).toBe(false)
  }
  await expect(page.getByText(/jawaban individual/i)).toHaveCount(0)
})
