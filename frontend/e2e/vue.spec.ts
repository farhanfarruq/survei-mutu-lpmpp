import { expect, test } from '@playwright/test'

test('external respondent completes the production response flow at mobile width', async ({
  page,
}) => {
  let version = 1
  const survey = {
    id: 'survey-1',
    code: 'SRV-MVP',
    name: 'Survei Mutu Layanan',
    privacy_mode: 'anonymous',
    privacy_notice: 'Isi jawaban dipisahkan dari data partisipasi.',
    closes_at: '2026-12-31T23:59:59+07:00',
    question_count: 1,
    estimated_minutes: 1,
    participation_status: 'eligible',
    sections: [
      {
        id: 'section-1',
        code: 'SEC',
        title: 'Layanan',
        description: 'Nilai pengalaman Anda.',
        position: 1,
        questions: [
          {
            id: 'question-1',
            code: 'Q1',
            text: 'Bagaimana mutu layanan?',
            response_type: 'scale',
            required: true,
            validation: null,
            options: [{ value: '4', label: 'Sangat baik' }],
            na_allowed: false,
          },
        ],
      },
    ],
  }

  await page.route('**/api/v1/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    const method = route.request().method()
    const data =
      path === '/api/v1/respondent-sessions'
        ? {
            session_token: 'session-token',
            completion_token: 'completion-token',
            expires_at: survey.closes_at,
          }
        : path === '/api/v1/respondent-survey'
          ? survey
          : path === '/api/v1/responses' && method === 'POST'
            ? {
                id: 'response-1',
                state: 'started',
                version,
                progress: 0,
                survey,
                answers: [],
                receipt: null,
              }
            : path === '/api/v1/responses/response-1' && method === 'GET'
              ? {
                  id: 'response-1',
                  state: 'started',
                  version,
                  progress: 0,
                  survey,
                  answers: [],
                  receipt: null,
                }
              : path === '/api/v1/responses/response-1' && method === 'PATCH'
                ? {
                    id: 'response-1',
                    state: 'partial',
                    version: ++version,
                    progress: 100,
                    survey,
                    answers: [{ question_id: 'question-1', value: '4' }],
                    receipt: null,
                  }
                : {
                    receipt_code: 'SM-202608-TEST123456',
                    submitted_at: '2026-08-08T08:00:00+07:00',
                    response_id: 'response-1',
                  }
    await route.fulfill({
      status: path.endsWith('/submissions')
        ? 200
        : path === '/api/v1/responses' || path === '/api/v1/respondent-sessions'
          ? 201
          : 200,
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    })
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
  await expect(page.getByRole('link', { name: 'Kembali ke halaman awal' })).toHaveAttribute(
    'href',
    '/',
  )
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
    ),
  ).toBe(false)
})

test('Phase 13 production workspaces are permission-aware and reflow on mobile', async ({
  page,
}) => {
  await page.route('**/api/v1/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    const data =
      path === '/api/v1/me'
        ? {
            id: 'user-public',
            name: 'Pimpinan Fiktif',
            email: 'pimpinan@example.test',
            is_active: true,
            roles: ['leader'],
            permissions: [
              'admin.panel.access',
              'ai.read',
              'notification.read',
              'finding.read',
              'follow-up.dashboard.read',
            ],
            organizational_units: [
              {
                id: 'unit-1',
                code: 'UNIT',
                name: 'Unit Fiktif',
                scope_mode: 'self',
                is_primary: true,
              },
            ],
          }
        : path === '/api/v1/ai-provider-configs' ||
            path === '/api/v1/ai-prompt-templates' ||
            path === '/api/v1/findings'
          ? []
          : path === '/api/v1/notifications'
            ? [
                {
                  id: 'notification-1',
                  type: 'ai_failure',
                  title: 'AI memakai fallback',
                  message: 'Gunakan statistik deterministik.',
                  route: '/app/analytics',
                  context: {},
                  read_at: null,
                  created_at: '2026-08-08T08:00:00+07:00',
                },
              ]
            : { counts: {}, total: 0, overdue: 0, pending_verification: 0, revision: 0, items: [] }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(
        path === '/api/v1/notifications' ? { data, meta: { unread: 1 } } : { data },
      ),
    })
  })

  await page.setViewportSize({ width: 320, height: 800 })
  for (const [path, heading] of [
    ['/app/ai', 'Analisis AI'],
    ['/app/notifications', 'Notifikasi'],
    ['/app/follow-up', 'Tindak Lanjut'],
  ] as const) {
    await page.goto(path)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading)
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
      ),
      `${path} must reflow at 320px`,
    ).toBe(false)
  }
  await expect(page.getByText(/jawaban individual/i)).toHaveCount(0)
})
