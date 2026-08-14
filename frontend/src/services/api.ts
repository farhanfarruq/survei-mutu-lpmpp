import axios, { AxiosError } from 'axios'

export type ProblemError = {
  message: string
  code?: string
  requestId?: string
  fields: Record<string, string[]>
  status?: number
}

type ProblemPayload = {
  detail?: string
  code?: string
  request_id?: string
  errors?: Array<{ pointer?: string; detail?: string }>
}

export const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000').replace(/\/$/, '')

export const api = axios.create({
  baseURL: apiBaseUrl,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export async function initializeCsrf(): Promise<void> {
  await api.get('/sanctum/csrf-cookie')
}

export function normalizeApiError(error: unknown): ProblemError {
  if (!axios.isAxiosError<ProblemPayload>(error)) {
    return { message: 'Terjadi kesalahan yang tidak diketahui.', fields: {} }
  }

  const payload = error.response?.data
  const fields: Record<string, string[]> = {}

  for (const item of payload?.errors ?? []) {
    const field = item.pointer?.replace(/^\//, '').replace(/\//g, '.') ?? 'general'
    fields[field] ??= []
    fields[field].push(item.detail ?? 'Nilai tidak valid.')
  }

  return {
    message: payload?.detail ?? (error as AxiosError).message ?? 'Permintaan gagal.',
    code: payload?.code,
    requestId: payload?.request_id,
    fields,
    status: error.response?.status,
  }
}
