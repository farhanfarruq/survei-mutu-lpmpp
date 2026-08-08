import { ref } from 'vue'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { useResponseAutosave } from '@/composables/useResponseAutosave'
import { getResponse, saveResponse, type ResponseDraft } from '@/services/responses'

describe('response autosave recovery', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    localStorage.clear()
  })

  afterEach(() => vi.useRealTimers())

  it('keeps the local draft on network failure and retries without losing answers', async () => {
    const answers = ref<Record<string, unknown>>({ question: 'jawaban lokal' })
    const save = vi.fn<typeof saveResponse>().mockRejectedValue({ isAxiosError: true, message: 'Network Error' })
    const fetch = vi.fn<typeof getResponse>()
    const autosave = useResponseAutosave('response-1', 'session-token', 1, answers, { save, fetch }, 500)

    autosave.schedule()
    await vi.advanceTimersByTimeAsync(500)

    expect(autosave.status.value).toBe('failed')
    expect(localStorage.getItem('simutu:response:response-1')).toContain('jawaban lokal')

    const recoveredAnswers = ref<Record<string, unknown>>({})
    const recovered = useResponseAutosave('response-1', 'session-token', 1, recoveredAnswers, { save, fetch }, 500)
    expect(recovered.recover()).toBe(true)
    expect(recoveredAnswers.value).toEqual({ question: 'jawaban lokal' })

    save.mockResolvedValue({ version: 2 } as ResponseDraft)
    recovered.schedule()
    await recovered.flush()
    expect(recovered.status.value).toBe('saved')
    expect(recovered.version.value).toBe(2)
  })

  it('refetches and reapplies local answers after an autosave version conflict', async () => {
    const answers = ref<Record<string, unknown>>({ local: 'baru' })
    const save = vi.fn<typeof saveResponse>()
      .mockRejectedValueOnce({ isAxiosError: true, response: { status: 412, data: { code: 'version_conflict' } } })
      .mockResolvedValueOnce({ version: 4 } as ResponseDraft)
    const fetch = vi.fn<typeof getResponse>().mockResolvedValue({ version: 3, answers: [{ question_id: 'server', value: 'lama' }] } as ResponseDraft)
    const autosave = useResponseAutosave('response-2', 'session-token', 2, answers, { save, fetch }, 500)

    autosave.schedule()
    await autosave.flush()

    expect(fetch).toHaveBeenCalledOnce()
    expect(save).toHaveBeenCalledTimes(2)
    expect(answers.value).toEqual({ server: 'lama', local: 'baru' })
    expect(autosave.version.value).toBe(4)
  })
})
