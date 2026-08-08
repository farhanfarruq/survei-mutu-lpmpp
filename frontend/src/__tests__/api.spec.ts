import { describe, expect, it } from 'vitest'

import { normalizeApiError } from '@/services/api'

describe('normalizeApiError', () => {
  it('maps Problem Details fields and request id without leaking raw payloads', () => {
    const error = {
      isAxiosError: true,
      message: 'Request failed',
      response: {
        status: 422,
        data: {
          detail: 'Data tidak valid.',
          code: 'validation_failed',
          request_id: 'request-123',
          errors: [{ pointer: '/email', detail: 'Email wajib diisi.' }],
        },
      },
    }

    expect(normalizeApiError(error)).toEqual({
      message: 'Data tidak valid.',
      code: 'validation_failed',
      requestId: 'request-123',
      fields: { email: ['Email wajib diisi.'] },
      status: 422,
    })
  })

  it('returns a safe fallback for non-Axios errors', () => {
    expect(normalizeApiError(new Error('sensitive internal detail'))).toEqual({
      message: 'Terjadi kesalahan yang tidak diketahui.',
      fields: {},
    })
  })
})
