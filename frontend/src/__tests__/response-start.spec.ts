import { beforeEach, describe, expect, it, vi } from 'vitest'

import {
  startOrResumeAuthenticatedResponse,
  type ResponseDraft,
  type SessionCredentials,
} from '@/services/responses'

const mocks = vi.hoisted(() => ({
  post: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
}))

vi.mock('@/services/api', () => ({
  api: { post: mocks.post },
}))

describe('authenticated response start recovery', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    sessionStorage.clear()
  })

  it('reuses pending credentials when draft creation previously failed', async () => {
    const credentials: SessionCredentials = {
      session_token: 'session-token',
      completion_token: 'completion-token',
      expires_at: '2026-09-05T13:28:00Z',
    }
    const response = { id: 'response-1' } as ResponseDraft

    mocks.post
      .mockResolvedValueOnce({ data: { data: credentials } })
      .mockRejectedValueOnce(new Error('Network Error'))

    await expect(startOrResumeAuthenticatedResponse('survey-1')).rejects.toThrow('Network Error')
    expect(
      JSON.parse(sessionStorage.getItem('simutu:pending-credentials:survey-1') ?? 'null'),
    ).toEqual(credentials)

    mocks.post.mockClear()
    mocks.post.mockResolvedValueOnce({ data: { data: response } })

    await expect(startOrResumeAuthenticatedResponse('survey-1')).resolves.toEqual({
      credentials,
      response,
    })
    expect(mocks.post).toHaveBeenCalledExactlyOnceWith(
      '/api/v1/responses',
      expect.anything(),
      expect.anything(),
    )
    expect(sessionStorage.getItem('simutu:pending-credentials:survey-1')).toBeNull()
  })
})
