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
      identity_number: '20260001',
      account_type: 'student',
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
    await auth.login(user.identity_number ?? '', 'unused', false)

    expect(auth.user).toEqual(user)
    expect(mocks.initializeCsrf).not.toHaveBeenCalled()
    expect(mocks.post).not.toHaveBeenCalled()
  })

  it('registers a respondent with identity number and program study', async () => {
    const user: AuthUser = {
      id: 'user-2',
      name: 'Mahasiswa Baru',
      identity_number: '20260002',
      account_type: 'student',
      is_active: true,
      roles: ['respondent'],
      permissions: [],
      organizational_units: [],
    }
    mocks.initializeCsrf.mockResolvedValue()
    mocks.post.mockResolvedValue({})
    mocks.get.mockResolvedValue({ data: { data: user } })
    const auth = useAuthStore()

    await auth.register({
      name: user.name,
      identity_number: user.identity_number ?? '',
      account_type: 'student',
      organizational_unit_id: 'program-1',
      password: 'ValidPassphrase!123',
      password_confirmation: 'ValidPassphrase!123',
    })

    expect(mocks.initializeCsrf).toHaveBeenCalledOnce()
    expect(mocks.post).toHaveBeenNthCalledWith(
      1,
      '/api/v1/auth/register',
      expect.objectContaining({ identity_number: '20260002' }),
    )
    expect(mocks.post).toHaveBeenNthCalledWith(2, '/api/v1/auth/login', {
      identity_number: '20260002',
      password: 'ValidPassphrase!123',
      remember: false,
    })
    expect(auth.user).toEqual(user)
  })
})
