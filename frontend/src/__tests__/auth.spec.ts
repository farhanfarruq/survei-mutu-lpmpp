import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useAuthStore, type AuthUser } from '@/stores/auth'

const mocks = vi.hoisted(() => ({
  get: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
  post: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
  initializeCsrf: vi.fn<() => Promise<void>>(),
}))

vi.mock('@/services/api', () => ({
  api: { get: mocks.get, post: mocks.post },
  initializeCsrf: mocks.initializeCsrf,
  normalizeApiError: (error: { response?: { status?: number } }) => ({
    message: 'Request failed.',
    fields: {},
    status: error.response?.status,
  }),
}))

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('recovers a session created in another tab without posting login again', async () => {
    const user: AuthUser = {
      id: 'user-1',
      name: 'Test User',
      email: 'test@example.test',
      is_active: true,
      roles: ['super_admin'],
      permissions: [],
      organizational_units: [],
    }
    mocks.get
      .mockRejectedValueOnce({ response: { status: 401 } })
      .mockResolvedValueOnce({ data: { data: user } })
    const auth = useAuthStore()

    await auth.initialize()
    await auth.login(user.email, 'unused', false)

    expect(auth.user).toEqual(user)
    expect(mocks.initializeCsrf).not.toHaveBeenCalled()
    expect(mocks.post).not.toHaveBeenCalled()
  })
})
