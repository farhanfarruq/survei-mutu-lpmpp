import type { Ref } from 'vue'
import { ref } from 'vue'

import { normalizeApiError } from '@/services/api'
import { getResponse, saveResponse, type ResponseDraft } from '@/services/responses'

type AutosaveTransport = {
  save: typeof saveResponse
  fetch: typeof getResponse
}

const defaultTransport: AutosaveTransport = { save: saveResponse, fetch: getResponse }

export function useResponseAutosave(
  responseId: string,
  sessionToken: string,
  initialVersion: number,
  answers: Ref<Record<string, unknown>>,
  transport: AutosaveTransport = defaultTransport,
  delay = 1200,
) {
  const version = ref(initialVersion)
  const status = ref<'idle' | 'saving' | 'saved' | 'failed' | 'conflict'>('idle')
  const storageKey = `simutu:response:${responseId}`
  let timer: ReturnType<typeof setTimeout> | undefined
  let activeSave: Promise<void> | null = null

  function backup() {
    localStorage.setItem(storageKey, JSON.stringify({ version: version.value, answers: answers.value }))
  }

  function recover(): boolean {
    const stored = localStorage.getItem(storageKey)
    if (!stored) return false

    try {
      const draft = JSON.parse(stored) as { version?: number; answers?: Record<string, unknown> }
      if (!draft.answers) return false
      answers.value = { ...answers.value, ...draft.answers }
      return true
    } catch {
      localStorage.removeItem(storageKey)
      return false
    }
  }

  async function persist(retryingConflict = false): Promise<void> {
    status.value = retryingConflict ? 'conflict' : 'saving'
    backup()
    try {
      const saved = await transport.save(responseId, sessionToken, version.value, answers.value)
      version.value = saved.version
      status.value = 'saved'
      backup()
    } catch (caught) {
      if (normalizeApiError(caught).status === 412 && !retryingConflict) {
        const current = await transport.fetch(responseId, sessionToken)
        version.value = current.version
        answers.value = { ...Object.fromEntries(current.answers.map((answer) => [answer.question_id, answer.value])), ...answers.value }
        await persist(true)
        return
      }
      status.value = 'failed'
      throw caught
    }
  }

  function schedule() {
    backup()
    status.value = 'idle'
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      activeSave = persist().catch(() => undefined)
    }, delay)
  }

  async function flush(): Promise<void> {
    if (timer) {
      clearTimeout(timer)
      timer = undefined
      activeSave = persist()
    }
    await activeSave
  }

  function clear() {
    if (timer) clearTimeout(timer)
    localStorage.removeItem(storageKey)
  }

  function applyServerDraft(draft: ResponseDraft) {
    version.value = draft.version
    answers.value = Object.fromEntries(draft.answers.map((answer) => [answer.question_id, answer.value]))
  }

  return { version, status, schedule, flush, recover, clear, applyServerDraft }
}
