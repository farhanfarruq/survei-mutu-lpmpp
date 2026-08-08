<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { normalizeApiError } from '@/services/api'
import { createResponse, getEligibleSurvey, startAuthenticatedSurvey, type RespondentSurvey } from '@/services/responses'

const route = useRoute()
const router = useRouter()
const survey = ref<RespondentSurvey | null>(null)
const consent = ref(false)
const loading = ref(true)
const starting = ref(false)
const error = ref('')

onMounted(async () => {
  try {
    survey.value = await getEligibleSurvey(String(route.params.id))
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
})

async function start() {
  if (!consent.value || !survey.value) return
  starting.value = true
  error.value = ''
  try {
    const credentials = await startAuthenticatedSurvey(survey.value.id)
    const response = await createResponse(credentials)
    sessionStorage.setItem(`simutu:credentials:${response.id}`, JSON.stringify(credentials))
    await router.push(`/respond/responses/${response.id}`)
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    starting.value = false
  }
}
</script>

<template>
  <section class="response-page" aria-labelledby="survey-title">
    <RouterLink class="back-link" to="/app/surveys">← Kembali ke daftar</RouterLink>
    <p v-if="loading" role="status">Memuat detail survei…</p>
    <BaseAlert v-else-if="error && !survey" tone="error" title="Survei tidak dapat dibuka">{{ error }}</BaseAlert>
    <template v-else-if="survey">
      <div class="page-heading"><div><p class="eyebrow">{{ survey.code }}</p><h1 id="survey-title" tabindex="-1">{{ survey.name }}</h1><p class="lede">{{ survey.question_count }} pertanyaan · sekitar {{ survey.estimated_minutes }} menit</p></div></div>
      <div class="detail-grid">
        <article class="panel"><h2>Sebelum mulai</h2><dl class="definition-list"><div><dt>Batas waktu</dt><dd>{{ new Date(survey.closes_at).toLocaleString('id-ID') }}</dd></div><div><dt>Partisipasi</dt><dd>Sukarela; Anda dapat berhenti sebelum mengirim.</dd></div><div><dt>Mode privasi</dt><dd>{{ survey.privacy_mode === 'confidential' ? 'Rahasia dengan akses terbatas' : 'Isi jawaban dipisahkan dari identitas' }}</dd></div></dl></article>
        <article class="panel privacy-panel"><div><h2>Pemberitahuan privasi</h2><p>{{ survey.privacy_notice }}</p><p class="fine-print">Pimpinan hanya menerima hasil agregat yang telah melewati threshold pelaporan, bukan jawaban individual.</p></div></article>
      </div>
      <BaseAlert v-if="error" tone="error" title="Belum dapat memulai">{{ error }}</BaseAlert>
      <div class="consent-panel panel">
        <label class="consent-check"><input v-model="consent" type="checkbox" /> <span>Saya telah membaca pemberitahuan dan bersedia berpartisipasi.</span></label>
        <div class="button-row"><BaseButton :loading="starting" :disabled="!consent" @click="start">Setuju dan mulai</BaseButton><RouterLink class="button button-secondary" to="/app/surveys">Belum sekarang</RouterLink></div>
      </div>
    </template>
  </section>
</template>
