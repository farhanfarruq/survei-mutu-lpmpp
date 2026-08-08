import { api } from '@/services/api'

export type SurveyOption = { value: string; label: string; is_na?: boolean; exclusive?: boolean }
export type SurveyQuestion = {
  id: string
  code: string
  text: string
  help_text?: string | null
  response_type: 'scale' | 'single_choice' | 'multiple_choice' | 'short_text' | 'long_text' | 'number'
  required: boolean
  validation?: { min?: number; max?: number } | null
  options: SurveyOption[]
  na_allowed: boolean
}
export type SurveySection = { id: string; code: string; title: string; description?: string | null; position: number; questions: SurveyQuestion[] }
export type RespondentSurvey = {
  id: string
  code: string
  name: string
  privacy_mode: 'anonymous' | 'detached' | 'confidential'
  privacy_notice: string
  closes_at: string
  question_count: number
  estimated_minutes: number
  participation_status: 'eligible' | 'in_progress' | 'completed'
  sections: SurveySection[]
}
export type EligibleSurvey = Omit<RespondentSurvey, 'sections'>
export type SessionCredentials = { session_token: string; completion_token: string; expires_at: string }
export type ResponseDraft = {
  id: string
  state: 'started' | 'partial' | 'submitted'
  version: number
  progress: number
  survey: RespondentSurvey
  answers: Array<{ question_id: string; value: unknown }>
  receipt: { receipt_code: string; submitted_at: string } | null
}
export type CompletionReceipt = { receipt_code: string; submitted_at: string; response_id: string }
export type ResponseHistory = {
  survey_id: string
  survey_code: string
  survey_name: string
  privacy_mode: string
  status: 'eligible' | 'in_progress' | 'completed' | 'declined'
  completed_at: string | null
  closes_at: string
}

const respondentHeaders = (sessionToken: string) => ({ 'X-Respondent-Token': sessionToken })

export const newRequestKey = () => crypto.randomUUID()

export async function listEligibleSurveys(): Promise<EligibleSurvey[]> {
  return (await api.get<{ data: EligibleSurvey[] }>('/api/v1/surveys/eligible')).data.data
}

export async function getEligibleSurvey(id: string): Promise<RespondentSurvey> {
  return (await api.get<{ data: RespondentSurvey }>(`/api/v1/surveys/${id}/respondent-detail`)).data.data
}

export async function startAuthenticatedSurvey(id: string): Promise<SessionCredentials> {
  return (await api.post<{ data: SessionCredentials }>(`/api/v1/surveys/${id}/respondent-session`)).data.data
}

export async function exchangeInvitation(invitationToken: string): Promise<SessionCredentials> {
  return (await api.post<{ data: SessionCredentials }>('/api/v1/respondent-sessions', { invitation_token: invitationToken })).data.data
}

export async function getRespondentSurvey(sessionToken: string): Promise<RespondentSurvey> {
  return (await api.get<{ data: RespondentSurvey }>('/api/v1/respondent-survey', { headers: respondentHeaders(sessionToken) })).data.data
}

export async function createResponse(credentials: SessionCredentials): Promise<ResponseDraft> {
  return (
    await api.post<{ data: ResponseDraft }>(
      '/api/v1/responses',
      { consent: true, completion_token: credentials.completion_token },
      { headers: { ...respondentHeaders(credentials.session_token), 'Idempotency-Key': newRequestKey() } },
    )
  ).data.data
}

export async function declineParticipation(credentials: SessionCredentials): Promise<void> {
  await api.post('/api/v1/respondent-sessions/decline', { completion_token: credentials.completion_token })
}

export async function getResponse(id: string, sessionToken: string): Promise<ResponseDraft> {
  return (await api.get<{ data: ResponseDraft }>(`/api/v1/responses/${id}`, { headers: respondentHeaders(sessionToken) })).data.data
}

export async function saveResponse(
  id: string,
  sessionToken: string,
  version: number,
  answers: Record<string, unknown>,
): Promise<ResponseDraft> {
  return (
    await api.patch<{ data: ResponseDraft }>(
      `/api/v1/responses/${id}`,
      { answers: Object.entries(answers).map(([question_id, value]) => ({ question_id, value })) },
      { headers: { ...respondentHeaders(sessionToken), 'If-Match': `"${version}"`, 'Idempotency-Key': newRequestKey() } },
    )
  ).data.data
}

export async function submitResponse(
  id: string,
  credentials: SessionCredentials,
  version: number,
  idempotencyKey: string,
): Promise<CompletionReceipt> {
  return (
    await api.post<{ data: CompletionReceipt }>(
      `/api/v1/responses/${id}/submissions`,
      { completion_token: credentials.completion_token },
      { headers: { ...respondentHeaders(credentials.session_token), 'If-Match': `"${version}"`, 'Idempotency-Key': idempotencyKey } },
    )
  ).data.data
}

export async function getResponseHistory(): Promise<ResponseHistory[]> {
  return (await api.get<{ data: ResponseHistory[] }>('/api/v1/response-history')).data.data
}
