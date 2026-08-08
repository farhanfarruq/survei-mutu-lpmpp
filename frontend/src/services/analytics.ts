import { api } from '@/services/api'

export type MetricRow = {
  id?: string
  code: string
  name?: string
  text?: string
  n: number
  missing: number
  mean: number | null
  normalized_score: number | null
  interpretation: string | null
  top_two_box?: number | null
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

export type LeadershipDashboard = {
  summary: null | {
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

export type DashboardFilters = { unit_id?: string; period_id?: string; survey_id?: string; group_id?: string; drilldown?: 'item' }

export async function fetchLeadershipDashboard(filters: DashboardFilters = {}): Promise<LeadershipDashboard> {
  const response = await api.get<{ data: LeadershipDashboard }>('/api/v1/leadership/results', { params: filters })
  return response.data.data
}

export async function requestReportExport(snapshotId: string, format: 'csv' | 'json'): Promise<{ id: string; state: string; expires_at: string | null }> {
  const response = await api.post<{ data: { id: string; state: string; expires_at: string | null } }>(
    '/api/v1/report-exports',
    { aggregate_snapshot_id: snapshotId, format, filters: {} },
    { headers: { 'Idempotency-Key': crypto.randomUUID() } },
  )
  return response.data.data
}
