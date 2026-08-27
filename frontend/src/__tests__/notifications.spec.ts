import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useNotificationsStore } from '@/stores/notifications'

const mocks = vi.hoisted(() => ({
  notifications: vi.fn(),
  readNotification: vi.fn(),
  readAllNotifications: vi.fn(),
}))

vi.mock('@/services/phase13', () => ({ phase13Api: mocks }))
vi.mock('@/services/api', () => ({
  normalizeApiError: () => ({ message: 'Request failed.' }),
}))

describe('notifications store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('keeps the unread badge and read-all state consistent', async () => {
    const item = {
      id: 'notification-1',
      type: 'report_completion',
      title: 'Laporan selesai',
      message: 'Laporan siap dibaca.',
      route: '/app/analytics',
      context: {},
      read_at: null,
      created_at: '2026-08-18T10:00:00Z',
    }
    mocks.notifications.mockResolvedValue({ data: [item], meta: { unread: 120 } })
    mocks.readNotification.mockResolvedValue({
      data: { id: item.id, read_at: '2026-08-18T10:05:00Z' },
      meta: { unread: 119 },
    })
    mocks.readAllNotifications.mockResolvedValue({ meta: { unread: 0 } })
    const store = useNotificationsStore()

    await store.load()
    expect(store.unreadLabel).toBe('99+')

    const first = store.items[0]
    expect(first).toBeDefined()
    await store.markRead(first!)
    expect(first!.read_at).toBe('2026-08-18T10:05:00Z')
    expect(store.unread).toBe(119)

    await store.readAll()
    expect(store.unread).toBe(0)
  })
})
