import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { downloadReportExport } from '@/services/analytics'

const mocks = vi.hoisted(() => ({
  get: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
  post: vi.fn<(...args: unknown[]) => Promise<unknown>>(),
}))

vi.mock('@/services/api', () => ({ api: { get: mocks.get, post: mocks.post } }))

describe('dashboard report export', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.clearAllMocks()
    vi.stubGlobal('URL', {
      createObjectURL: vi.fn(() => 'blob:report'),
      revokeObjectURL: vi.fn(),
    })
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
  })

  it('waits for the export, creates a ticket, and downloads the file', async () => {
    const click = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})
    mocks.post
      .mockResolvedValueOnce({
        data: { data: { id: 'export-1', state: 'queued', format: 'pdf', error: null } },
      })
      .mockResolvedValueOnce({ data: { data: { download_token: 'ticket-1' } } })
    mocks.get
      .mockResolvedValueOnce({
        data: { data: { id: 'export-1', state: 'completed', format: 'pdf', error: null } },
      })
      .mockResolvedValueOnce({ data: new Blob(['%PDF-1.4']) })

    const download = downloadReportExport('snapshot-1', 'pdf')
    await vi.runAllTimersAsync()
    await download

    expect(mocks.get).toHaveBeenNthCalledWith(1, '/api/v1/report-exports/export-1')
    expect(mocks.post).toHaveBeenNthCalledWith(
      2,
      '/api/v1/report-exports/export-1/download-tickets',
    )
    expect(mocks.get).toHaveBeenNthCalledWith(2, '/api/v1/report-downloads/ticket-1', {
      responseType: 'blob',
    })
    expect(click).toHaveBeenCalledOnce()
  })
})
