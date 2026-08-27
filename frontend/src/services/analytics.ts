import { api } from '@/services/api'

export type MetricRow = {
  id?: string
  code: string
  name?: string
  text?: string
  category_code?: string | null
  category_name?: string | null
  indicator_name?: string | null
  response_type?: string | null
  n: number
  missing: number
  mean: number | null
  normalized_score: number | null
  interpretation: string | null
  top_two_box?: number | null
  distribution?: Array<{
    value: string | number
    label?: string
    count: number
    percentage: number
  }>
  suppressed: boolean
}

export type DashboardSeries = {
  snapshot_id: string
  survey_id: string
  survey: string
  unit_id: string
  unit: string
  period_id: string
  period: string
  group_id: string | null
  group: string | null
  n: number
  score: number | null
  comparison_eligible: boolean
  last_updated_at: string
}

export type DashboardSurveyOption = {
  id: string
  name: string
  state: 'scheduled' | 'active' | 'closed' | 'archived'
  responses_count: number
  reporting_threshold: number
  has_released_result: boolean
  unit_id: string
  unit: string
  period_id: string
  period: string
}

export type LeadershipDashboard = {
  survey_options: DashboardSurveyOption[]
  selected_survey: DashboardSurveyOption | null
  summary: null | {
    survey_id: string
    survey: string
    unit: string
    period: string
    response_rate: { submitted: number; eligible: number; percentage: number | null }
    overall: MetricRow
    categories: MetricRow[]
    limitations: string[]
    last_updated_at: string
  }
  comparison: { allowed: boolean; minimum_n: number; series: DashboardSeries[] }
  trend: { allowed: boolean; series: DashboardSeries[] }
  drilldown: MetricRow[]
  filter_provenance: Record<string, unknown>
  accessible_summary: string
}

export type DashboardFilters = {
  unit_id?: string
  period_id?: string
  survey_id?: string
  group_id?: string
  drilldown?: 'item'
}

export type ReportExportFormat = 'csv' | 'json' | 'pdf'

type ReportExport = {
  id: string
  state: string
  format: ReportExportFormat
  expires_at: string | null
  error: string | null
}

export async function fetchLeadershipDashboard(
  filters: DashboardFilters = {},
): Promise<LeadershipDashboard> {
  const response = await api.get<{ data: LeadershipDashboard }>('/api/v1/leadership/results', {
    params: filters,
  })
  return response.data.data
}

export async function requestReportExport(
  snapshotId: string,
  format: ReportExportFormat,
): Promise<ReportExport> {
  const response = await api.post<{ data: ReportExport }>(
    '/api/v1/report-exports',
    { aggregate_snapshot_id: snapshotId, format, filters: {} },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}

export async function downloadReportExport(
  snapshotId: string,
  format: ReportExportFormat,
): Promise<void> {
  let report = await requestReportExport(snapshotId, format)

  for (let attempt = 0; ['queued', 'running'].includes(report.state) && attempt < 30; attempt++) {
    await new Promise((resolve) => window.setTimeout(resolve, 1000))
    const response = await api.get<{ data: ReportExport }>(`/api/v1/report-exports/${report.id}`)
    report = response.data.data
  }

  if (report.state !== 'completed') {
    throw new Error(report.error || 'Ekspor belum selesai. Silakan coba lagi beberapa saat.')
  }

  const ticket = await api.post<{ data: { download_token: string } }>(
    `/api/v1/report-exports/${report.id}/download-tickets`,
  )
  const response = await api.get<Blob>(
    `/api/v1/report-downloads/${ticket.data.data.download_token}`,
    { responseType: 'blob' },
  )
  const url = URL.createObjectURL(response.data)
  const link = document.createElement('a')
  link.href = url
  link.download = `laporan-dashboard-${report.id}.${format}`
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.setTimeout(() => URL.revokeObjectURL(url), 0)
}
