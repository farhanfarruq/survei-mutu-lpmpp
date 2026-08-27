import { api } from '@/services/api'

export type AiConfig = {
  id: string
  provider: string
  model: string
  base_url: string
  secret_masked: string | null
  enabled: boolean
  limits: Record<string, number>
  connection_status: string
  last_tested_at: string | null
}
export type AiPrompt = {
  id: string
  use_case: string
  version: number
  active: boolean
  checksum: string
}
export type AiJob = {
  id: string
  state: string
  source_scope: Record<string, unknown>
  failure_code: string | null
  result_id: string | null
}
export type AiResult = {
  id: string
  label: string
  content: Record<string, unknown>
  source_scope: Record<string, unknown>
  provider: string
  model: string
  generated_at: string
}
export type AiWorkspaceOptions = {
  runs: Array<{
    id: string
    survey: string
    unit_id: string
    unit: string | null
    period: string | null
    completed_at: string
  }>
  jobs: Array<{
    id: string
    survey: string
    unit: string | null
    state: string
    created_at: string
  }>
}
export type AppNotification = {
  id: string
  type: string
  title: string
  message: string
  route: string | null
  context: Record<string, unknown>
  read_at: string | null
  created_at: string
}
export type FollowUpAction = {
  id: string
  finding_id: string
  title: string
  pic: { id: number; name: string } | null
  verifier: { id: number; name: string } | null
  root_cause: string
  plan: string
  expected_output: string
  resource_needs: string | null
  assignment_note: string | null
  state: string
  progress: number
  due_on: string
  revision_count: number
  version: number
  evidence: unknown[]
  verifications: unknown[]
}
export type Finding = {
  id: string
  code: string
  source_type: string
  owner_unit_id: string
  unit: string
  title: string
  description: string
  source_evidence: string
  severity: string
  state: string
  due_on: string
  version: number
  actions: FollowUpAction[]
}
export type FollowUpDashboard = {
  counts: Record<string, number>
  total: number
  overdue: number
  pending_verification: number
  revision: number
  items: Array<{
    id: string
    finding_code: string
    title: string
    unit: string
    state: string
    progress: number
    due_on: string
    overdue: boolean
  }>
}
export type Assignee = { id: number; name: string; can_update: boolean; can_verify: boolean }

export const phase13Api = {
  async aiWorkspaceOptions() {
    return (await api.get<{ data: AiWorkspaceOptions }>('/api/v1/ai-workspace-options')).data.data
  },
  async aiConfigs() {
    return (await api.get<{ data: AiConfig[] }>('/api/v1/ai-provider-configs')).data.data
  },
  async saveAiConfig(payload: Record<string, unknown>) {
    return (await api.post<{ data: AiConfig }>('/api/v1/ai-provider-configs', payload)).data.data
  },
  async testAiConfig(id: string) {
    return (
      await api.post<{ data: { status: string } }>(
        `/api/v1/ai-provider-configs/${id}/connection-tests`,
      )
    ).data.data
  },
  async prompts() {
    return (await api.get<{ data: AiPrompt[] }>('/api/v1/ai-prompt-templates')).data.data
  },
  async savePrompt(system_prompt: string) {
    return (
      await api.post<{ data: AiPrompt }>('/api/v1/ai-prompt-templates', {
        use_case: 'comprehensive_insight',
        system_prompt,
        active: true,
      })
    ).data.data
  },
  async createAiJob(runId: string, provider_config_id: string, prompt_template_id: string) {
    return (
      await api.post<{ data: AiJob }>(`/api/v1/analysis-runs/${runId}/ai-jobs`, {
        provider_config_id,
        prompt_template_id,
      })
    ).data.data
  },
  async aiJob(id: string) {
    return (await api.get<{ data: AiJob }>(`/api/v1/ai-jobs/${id}`)).data.data
  },
  async aiResult(id: string) {
    return (await api.get<{ data: AiResult }>(`/api/v1/ai-results/${id}`)).data.data
  },
  async notifications() {
    return (
      await api.get<{ data: AppNotification[]; meta: { unread: number } }>('/api/v1/notifications')
    ).data
  },
  async readNotification(id: string) {
    return (
      await api.post<{ data: { id: string; read_at: string }; meta: { unread: number } }>(
        `/api/v1/notifications/${id}/read`,
      )
    ).data
  },
  async readAllNotifications() {
    return (await api.post<{ meta: { unread: number } }>('/api/v1/notifications/read-all')).data
  },
  async findings(state = '') {
    return (
      await api.get<{ data: Finding[] }>('/api/v1/findings', {
        params: { state: state || undefined },
      })
    ).data.data
  },
  async assignees(unitId: string) {
    return (
      await api.get<{ data: Assignee[] }>('/api/v1/follow-up-assignees', {
        params: { unit_id: unitId },
      })
    ).data.data
  },
  async createFinding(payload: Record<string, unknown>) {
    return (await api.post<{ data: Finding }>('/api/v1/findings', payload)).data.data
  },
  async createAction(findingId: string, payload: Record<string, unknown>) {
    return (
      await api.post<{ data: FollowUpAction }>(`/api/v1/findings/${findingId}/actions`, payload)
    ).data.data
  },
  async followUpDashboard() {
    return (await api.get<{ data: FollowUpDashboard }>('/api/v1/follow-up/dashboard')).data.data
  },
  async action(id: string) {
    return (await api.get<{ data: FollowUpAction }>(`/api/v1/follow-up-actions/${id}`)).data.data
  },
  async updateAction(action: FollowUpAction, payload: Record<string, unknown>) {
    return (
      await api.patch<{ data: FollowUpAction }>(`/api/v1/follow-up-actions/${action.id}`, payload, {
        headers: { 'If-Match': `"${action.version}"` },
      })
    ).data.data
  },
  async addEvidence(actionId: string, payload: Record<string, unknown>) {
    await api.post(`/api/v1/follow-up-actions/${actionId}/evidence`, payload)
  },
  async submitAction(action: FollowUpAction) {
    return (
      await api.post<{ data: FollowUpAction }>(
        `/api/v1/follow-up-actions/${action.id}/verification-submissions`,
        {},
        { headers: { 'If-Match': `"${action.version}"` } },
      )
    ).data.data
  },
  async verifyAction(action: FollowUpAction, payload: Record<string, unknown>) {
    return (
      await api.post<{ data: FollowUpAction }>(
        `/api/v1/follow-up-actions/${action.id}/verification-decisions`,
        payload,
        { headers: { 'If-Match': `"${action.version}"` } },
      )
    ).data.data
  },
}
