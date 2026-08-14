<script setup lang="ts">
import { computed, nextTick, onMounted, ref, shallowRef } from 'vue'
import { useRoute } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useResponseAutosave } from '@/composables/useResponseAutosave'
import { normalizeApiError } from '@/services/api'
import {
  getResponse,
  newRequestKey,
  submitResponse,
  type CompletionReceipt,
  type ResponseDraft,
  type SessionCredentials,
  type SurveyOption,
  type SurveyQuestion,
} from '@/services/responses'

const route = useRoute()
const draft = ref<ResponseDraft | null>(null)
const answers = ref<Record<string, unknown>>({})
const currentSection = ref(0)
const missing = ref(new Set<string>())
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const receipt = ref<CompletionReceipt | null>(null)
const confirmDialog = ref<HTMLDialogElement | null>(null)
const autosave = shallowRef<ReturnType<typeof useResponseAutosave> | null>(null)
let credentials: SessionCredentials | null = null

const sections = computed(() => draft.value?.survey.sections ?? [])
const current = computed(() => sections.value[currentSection.value] ?? null)
const questions = computed(() => sections.value.flatMap((section) => section.questions))
const answeredCount = computed(
  () => questions.value.filter((question) => !isBlank(answers.value[question.id])).length,
)
const progress = computed(() =>
  questions.value.length ? Math.round((answeredCount.value / questions.value.length) * 100) : 0,
)
const saveStatus = computed(() => autosave.value?.status.value ?? 'idle')
const saveLabel = computed(
  () =>
    ({
      idle: 'Perubahan belum dikirim',
      saving: 'Menyimpan…',
      saved: 'Tersimpan',
      failed: 'Gagal tersimpan; draf aman di perangkat',
      conflict: 'Menyelaraskan versi draf…',
    })[saveStatus.value],
)

onMounted(async () => {
  const responseId = String(route.params.id)
  try {
    const raw = sessionStorage.getItem(`simutu:credentials:${responseId}`)
    if (!raw) throw new Error('respondent-session-missing')
    credentials = JSON.parse(raw) as SessionCredentials
    draft.value = await getResponse(responseId, credentials.session_token)
    answers.value = Object.fromEntries(
      draft.value.answers.map((answer) => [answer.question_id, answer.value]),
    )
    autosave.value = useResponseAutosave(
      responseId,
      credentials.session_token,
      draft.value.version,
      answers,
    )
    if (autosave.value.recover()) autosave.value.schedule()
    if (draft.value.receipt) receipt.value = { ...draft.value.receipt, response_id: draft.value.id }
  } catch (caught) {
    error.value =
      caught instanceof Error && caught.message === 'respondent-session-missing'
        ? 'Sesi pengisian tidak ditemukan pada tab ini. Buka kembali undangan atau daftar survei Anda.'
        : normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
})

function isBlank(value: unknown): boolean {
  return (
    value === undefined ||
    value === null ||
    value === '' ||
    (Array.isArray(value) && value.length === 0)
  )
}

function inputValue(questionId: string): string | number {
  const value = answers.value[questionId]
  return typeof value === 'string' || typeof value === 'number' ? value : ''
}

function changed(question: SurveyQuestion, value: unknown) {
  answers.value = { ...answers.value, [question.id]: value }
  if (!isBlank(value)) {
    const next = new Set(missing.value)
    next.delete(question.id)
    missing.value = next
  }
  autosave.value?.schedule()
}

function toggleChoice(question: SurveyQuestion, option: SurveyOption, checked: boolean) {
  const selected = Array.isArray(answers.value[question.id])
    ? [...(answers.value[question.id] as string[])]
    : []
  if (option.exclusive && checked) return changed(question, [option.value])
  const withoutExclusive = selected.filter(
    (value) => !question.options.find((candidate) => candidate.value === value)?.exclusive,
  )
  changed(
    question,
    checked
      ? [...withoutExclusive, option.value]
      : withoutExclusive.filter((value) => value !== option.value),
  )
}

function validateRequired(): boolean {
  const invalid = questions.value.filter(
    (question) => question.required && isBlank(answers.value[question.id]),
  )
  missing.value = new Set(invalid.map((question) => question.id))
  if (!invalid.length) return true

  currentSection.value = Math.max(
    0,
    sections.value.findIndex((section) =>
      section.questions.some((question) => question.id === invalid[0]?.id),
    ),
  )
  void nextTick(() =>
    document.querySelector<HTMLElement>(`[data-question-id="${invalid[0]?.id}"]`)?.focus(),
  )
  return false
}

async function review() {
  error.value = ''
  if (!validateRequired()) return
  try {
    await autosave.value?.flush()
    if (autosave.value?.status.value === 'failed') throw new Error('autosave-failed')
    confirmDialog.value?.showModal()
  } catch {
    error.value = 'Jawaban belum tersimpan ke server. Periksa koneksi lalu pilih Coba simpan lagi.'
  }
}

async function retrySave() {
  error.value = ''
  try {
    autosave.value?.schedule()
    await autosave.value?.flush()
  } catch {
    error.value = 'Jaringan masih belum tersedia. Draf tetap tersimpan pada perangkat ini.'
  }
}

async function submit() {
  if (!draft.value || !credentials || !autosave.value) return
  submitting.value = true
  error.value = ''
  try {
    const keyName = `simutu:submission-key:${draft.value.id}`
    const idempotencyKey = sessionStorage.getItem(keyName) ?? newRequestKey()
    sessionStorage.setItem(keyName, idempotencyKey)
    receipt.value = await submitResponse(
      draft.value.id,
      credentials,
      autosave.value.version.value,
      idempotencyKey,
    )
    autosave.value.clear()
    sessionStorage.removeItem(keyName)
    confirmDialog.value?.close()
    await nextTick()
    document.querySelector<HTMLElement>('#completion-title')?.focus()
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main id="main-content" class="public-response-page">
    <section class="response-page" aria-labelledby="response-title">
      <p v-if="loading" role="status">Memulihkan draf…</p>
      <BaseAlert v-else-if="error && !draft" tone="error" title="Draf tidak dapat dibuka">{{
        error
      }}</BaseAlert>
      <div v-else-if="receipt" class="success-panel">
        <div class="success-icon" aria-hidden="true">✓</div>
        <p class="eyebrow">Respons diterima</p>
        <h1 id="completion-title" tabindex="-1">Terima kasih telah berpartisipasi</h1>
        <p>
          Jawaban telah dibekukan dan tidak dapat diubah. Simpan kode konfirmasi nonidentifying
          berikut.
        </p>
        <strong class="receipt-code">{{ receipt.receipt_code }}</strong>
        <p class="fine-print">
          Diterima {{ new Date(receipt.submitted_at).toLocaleString('id-ID') }}. Kode ini tidak
          membuka isi jawaban.
        </p>
        <RouterLink class="button button-primary" to="/">Kembali ke halaman awal</RouterLink>
      </div>
      <template v-else-if="draft">
        <div class="survey-form-head">
          <div>
            <p class="eyebrow">{{ draft.survey.code }}</p>
            <h1 id="response-title" tabindex="-1">{{ draft.survey.name }}</h1>
          </div>
          <div class="autosave" role="status" aria-live="polite">
            <span class="autosave-dot" :class="{ failed: saveStatus === 'failed' }" />{{
              saveLabel
            }}
          </div>
        </div>
        <div class="progress-label">
          <span>Progres jawaban</span><strong>{{ progress }}%</strong>
        </div>
        <progress :value="progress" max="100">{{ progress }}%</progress>
        <BaseAlert v-if="missing.size" tone="error" title="Jawaban wajib belum lengkap"
          ><p>{{ missing.size }} pertanyaan wajib perlu dijawab sebelum dikirim.</p></BaseAlert
        >
        <BaseAlert v-if="error" tone="error" title="Permintaan belum berhasil"
          ><p>{{ error }}</p>
          <button v-if="saveStatus === 'failed'" class="text-link" type="button" @click="retrySave">
            Coba simpan lagi
          </button></BaseAlert
        >
        <div class="form-layout">
          <nav class="stepper" aria-label="Bagian survei">
            <strong>Bagian</strong>
            <ol>
              <li
                v-for="(section, index) in sections"
                :key="section.id"
                :class="{ current: currentSection === index }"
              >
                <button
                  type="button"
                  :aria-current="currentSection === index ? 'step' : undefined"
                  @click="currentSection = index"
                >
                  <span>{{ index + 1 }}</span
                  >{{ section.title }}
                </button>
              </li>
            </ol>
            <p>Draf dipulihkan hanya dari sesi dan perangkat yang diizinkan.</p>
          </nav>
          <form class="survey-form" @submit.prevent="review">
            <section v-if="current" class="panel" :aria-labelledby="`section-${current.id}`">
              <h2 :id="`section-${current.id}`">{{ current.title }}</h2>
              <p v-if="current.description">{{ current.description }}</p>
              <fieldset
                v-for="question in current.questions"
                :key="question.id"
                class="question-field"
                :class="{ invalid: missing.has(question.id) }"
                :data-question-id="question.id"
                tabindex="-1"
              >
                <legend>
                  <span>{{ question.code }}</span
                  >{{ question.text }} <em v-if="question.required">Wajib</em
                  ><em v-else>Opsional</em>
                </legend>
                <p v-if="question.help_text" :id="`help-${question.id}`" class="indicator">
                  {{ question.help_text }}
                </p>
                <div
                  v-if="
                    question.response_type === 'scale' || question.response_type === 'single_choice'
                  "
                  class="scale-options"
                >
                  <label v-for="option in question.options" :key="option.value"
                    ><input
                      type="radio"
                      :name="question.id"
                      :value="option.value"
                      :checked="answers[question.id] === option.value"
                      @change="changed(question, option.value)"
                    /><span>{{ option.label }}</span></label
                  >
                  <label v-if="question.na_allowed"
                    ><input
                      type="radio"
                      :name="question.id"
                      value="__na__"
                      :checked="answers[question.id] === '__na__'"
                      @change="changed(question, '__na__')"
                    /><span>Tidak relevan / N/A</span></label
                  >
                </div>
                <div v-else-if="question.response_type === 'multiple_choice'" class="choice-list">
                  <label v-for="option in question.options" :key="option.value"
                    ><input
                      type="checkbox"
                      :checked="
                        Array.isArray(answers[question.id]) &&
                        (answers[question.id] as string[]).includes(option.value)
                      "
                      @change="
                        toggleChoice(question, option, ($event.target as HTMLInputElement).checked)
                      "
                    />
                    <span>{{ option.label }}</span></label
                  >
                </div>
                <label v-else-if="question.response_type === 'short_text'" class="textarea-field"
                  ><span class="sr-only">Jawaban {{ question.code }}</span
                  ><input
                    type="text"
                    maxlength="500"
                    :value="inputValue(question.id)"
                    @input="changed(question, ($event.target as HTMLInputElement).value)"
                /></label>
                <label v-else-if="question.response_type === 'long_text'" class="textarea-field"
                  ><span>Hindari nama, NIM, email, atau identitas langsung.</span
                  ><textarea
                    maxlength="5000"
                    :value="inputValue(question.id)"
                    @input="changed(question, ($event.target as HTMLTextAreaElement).value)"
                  />
                </label>
                <label v-else-if="question.response_type === 'number'" class="textarea-field"
                  ><span class="sr-only">Jawaban angka {{ question.code }}</span
                  ><input
                    type="number"
                    :min="question.validation?.min"
                    :max="question.validation?.max"
                    :value="inputValue(question.id)"
                    @input="
                      changed(
                        question,
                        ($event.target as HTMLInputElement).value === ''
                          ? null
                          : Number(($event.target as HTMLInputElement).value),
                      )
                    "
                /></label>
                <p v-if="missing.has(question.id)" class="field-error">
                  Pertanyaan ini wajib dijawab.
                </p>
              </fieldset>
            </section>
            <div class="form-actions">
              <BaseButton
                variant="secondary"
                :disabled="currentSection === 0"
                @click="currentSection--"
                >Sebelumnya</BaseButton
              ><BaseButton v-if="currentSection < sections.length - 1" @click="currentSection++"
                >Bagian berikutnya</BaseButton
              ><BaseButton v-else type="submit">Tinjau dan kirim</BaseButton>
            </div>
          </form>
        </div>
      </template>
    </section>
    <dialog ref="confirmDialog" class="dialog" aria-labelledby="confirm-title">
      <form method="dialog" @submit.prevent>
        <h2 id="confirm-title">Kirim respons final?</h2>
        <p>
          Setelah dikirim, jawaban tidak dapat diubah. Pimpinan hanya menerima hasil agregat yang
          memenuhi threshold.
        </p>
        <div class="dialog-actions">
          <BaseButton variant="secondary" @click="confirmDialog?.close()">Periksa lagi</BaseButton
          ><BaseButton :loading="submitting" @click="submit">Ya, kirim respons</BaseButton>
        </div>
      </form>
    </dialog>
  </main>
</template>
