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
    if (path === '/api/v1/notifications/read-all' && route.request().method() === 'POST') {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data: null, meta: { unread: 0 } }),
      })

      return
    }
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
              'action.read',
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
        : path === '/api/v1/ai-workspace-options'
          ? {
              runs: [],
              jobs: [
                {
                  id: 'job-1',
                  survey: 'Survei Kepuasan',
                  unit: 'Unit Fiktif',
                  state: 'completed',
                  created_at: '2026-08-17T13:59:34+07:00',
                },
              ],
            }
          : path === '/api/v1/ai-jobs/job-1'
            ? {
                id: 'job-1',
                state: 'completed',
                source_scope: {},
                failure_code: null,
                result_id: 'result-1',
              }
            : path === '/api/v1/ai-results/result-1'
              ? {
                  id: 'result-1',
                  label: 'Ringkasan AI — periksa sebelum digunakan',
                  content: {
                    summary: 'Kualitas layanan berada dalam kategori baik.',
                    topics: ['Kualitas Layanan', 'Survei Kepuasan'],
                    sentiment: { label: 'positive', confidence: 0.9 },
                    trend_explanation: 'Kecepatan respons masih perlu diperhatikan.',
                    recommendations: ['Tingkatkan kecepatan respons layanan.'],
                    limitations: ['Data yang ditampilkan hanya berupa agregat.'],
                  },
                  source_scope: {},
                  provider: 'OpenRouter',
                  model: 'openai/gpt-4o-mini',
                  generated_at: '2026-08-17T13:59:34+07:00',
                }
              : path === '/api/v1/findings'
                ? [
                    {
                      id: 'finding-1',
                      code: 'TM-UJI-001',
                      source_type: 'manual',
                      owner_unit_id: 'unit-1',
                      unit: 'Unit Fiktif',
                      title: 'Kecepatan layanan perlu ditingkatkan',
                      description: 'Finding untuk pengujian navigasi.',
                      source_evidence: 'Hasil survei agregat.',
                      severity: 'high',
                      state: 'in_progress',
                      due_on: '2026-08-31',
                      version: 1,
                      actions: [
                        {
                          id: 'action-1',
                          title: 'Perbaiki waktu respons',
                          state: 'in_progress',
                          progress: 50,
                          due_on: '2026-08-31',
                          revision_count: 0,
                          version: 1,
                          evidence: [],
                          verifications: [],
                        },
                      ],
                    },
                  ]
                : path === '/api/v1/follow-up-actions/action-1'
                  ? {
                      id: 'action-1',
                      finding_id: 'finding-1',
                      title: 'Perbaiki waktu respons',
                      pic: { id: 2, name: 'PIC Uji' },
                      verifier: { id: 3, name: 'Verifier Uji' },
                      root_cause: 'Alur respons belum terukur.',
                      plan: 'Tetapkan SLA dan pantau setiap minggu.',
                      expected_output: 'Waktu respons memenuhi SLA.',
                      resource_needs: null,
                      assignment_note: null,
                      state: 'in_progress',
                      progress: 50,
                      due_on: '2026-08-31',
                      revision_count: 0,
                      version: 1,
                      evidence: [],
                      verifications: [],
                    }
                  : path === '/api/v1/ai-provider-configs' || path === '/api/v1/ai-prompt-templates'
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
                      : {
                          counts: {},
                          total: 0,
                          overdue: 0,
                          pending_verification: 0,
                          revision: 0,
                          items: [],
                        }
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
    ['/app/ai?job=job-1', 'Analisis AI'],
    ['/app/notifications', 'Notifikasi'],
    ['/app/follow-up', 'Tindak Lanjut'],
  ] as const) {
    await page.goto(path)
    await expect(page.getByRole('heading', { level: 1 })).toHaveText(heading)
    if (path.startsWith('/app/ai')) {
      await expect(page.getByRole('heading', { name: 'Ringkasan utama' })).toBeVisible()
      await expect(page.getByText('Kualitas Layanan', { exact: true })).toBeVisible()
    }
    if (path === '/app/notifications') {
      await expect(page.getByRole('button', { name: 'Baca semua' })).toBeVisible()
      await page.getByRole('button', { name: 'Baca semua' }).click()
      await expect(page.getByText('Telah dibaca')).toBeVisible()
    }
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
      ),
      `${path} must reflow at 320px`,
    ).toBe(false)
  }
  await page.getByRole('link', { name: 'Perbaiki waktu respons' }).click()
  await expect(page).toHaveURL(/\/app\/follow-ups\/actions\/action-1$/)
  await expect(page.getByRole('heading', { name: 'Perbaiki waktu respons' })).toBeVisible()
  await page.getByRole('link', { name: 'Kembali' }).click()
  await expect(page).toHaveURL(/\/app\/follow-up$/)
  await expect(page.getByText('Kecepatan layanan perlu ditingkatkan')).toBeVisible()
  await expect(page.getByText(/jawaban individual/i)).toHaveCount(0)
})

test('dashboard replaces bar axes when answer chart changes to donut', async ({ page }) => {
  await page.route('**/api/v1/**', async (route) => {
    const path = new URL(route.request().url()).pathname
    const data =
      path === '/api/v1/me'
        ? {
            id: 'leader-chart',
            name: 'Pimpinan Fiktif',
            email: 'pimpinan@example.test',
            is_active: true,
            roles: ['leader'],
            permissions: ['admin.panel.access', 'report.read'],
            organizational_units: [],
          }
        : path === '/api/v1/leadership/results'
          ? {
              summary: {
                survey_id: 'survey-1',
                survey: 'Survei Layanan',
                unit: 'LPMPP',
                period: '2026',
                response_rate: { submitted: 448, eligible: 500, percentage: 89.6 },
                overall: {
                  code: 'overall',
                  n: 448,
                  missing: 0,
                  mean: 4.4,
                  normalized_score: 85,
                  interpretation: 'Baik',
                  suppressed: false,
                },
                categories: [
                  {
                    code: 'KEANDALAN',
                    name: 'Keandalan Layanan',
                    n: 448,
                    missing: 0,
                    mean: 4.4,
                    normalized_score: 85,
                    interpretation: 'Baik',
                    suppressed: false,
                  },
                ],
                limitations: [],
                last_updated_at: '2026-08-15T10:00:00+07:00',
              },
              comparison: {
                allowed: false,
                minimum_n: 30,
                series: [
                  {
                    snapshot_id: 'snapshot-1',
                    survey_id: 'survey-1',
                    survey: 'Survei Layanan',
                    unit_id: 'unit-1',
                    unit: 'LPMPP',
                    period_id: 'period-1',
                    period: '2026',
                    group_id: null,
                    group: null,
                    n: 448,
                    score: 85,
                    comparison_eligible: true,
                    last_updated_at: '2026-08-15T10:00:00+07:00',
                  },
                ],
              },
              trend: { allowed: false, series: [] },
              drilldown: [
                {
                  id: 'question-1',
                  code: 'KEANDALAN-01',
                  text: 'Keandalan Layanan',
                  category_code: 'KEANDALAN',
                  category_name: 'Keandalan Layanan',
                  indicator_name: 'Keandalan',
                  response_type: 'scale',
                  n: 448,
                  missing: 0,
                  mean: 4.4,
                  normalized_score: 85,
                  interpretation: 'Baik',
                  suppressed: false,
                  distribution: [
                    { value: 1, label: 'Sangat tidak setuju', count: 9, percentage: 2 },
                    { value: 2, label: 'Tidak setuju', count: 9, percentage: 2 },
                    { value: 3, label: 'Netral', count: 9, percentage: 2 },
                    { value: 4, label: 'Setuju', count: 188, percentage: 42 },
                    { value: 5, label: 'Sangat setuju', count: 233, percentage: 52 },
                  ],
                },
              ],
              filter_provenance: {},
              accessible_summary: 'Survei Layanan berdasarkan 448 respons.',
            }
          : []

    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    })
  })

  await page.goto('/app/analytics')
  const answerSection = page.locator('section[aria-labelledby="answer-chart-title"]')
  await expect(answerSection).toBeVisible()
  await answerSection.getByLabel('Tampilan').selectOption('donut')

  const chart = answerSection.locator('.analytics-chart')
  await expect(chart.getByText('Jumlah jawaban', { exact: true })).toHaveCount(0)
  await expect(chart.getByText('448', { exact: true })).toHaveCount(1)
  await expect(chart.getByText('Sangat tidak setuju', { exact: true })).toHaveCount(1)
  await expect(chart.getByText('Sangat setuju', { exact: true })).toHaveCount(2)
})
