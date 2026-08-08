<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { normalizeApiError } from '@/services/api'
import { createResponse, declineParticipation, exchangeInvitation, getRespondentSurvey, type RespondentSurvey, type SessionCredentials } from '@/services/responses'

const route = useRoute()
const router = useRouter()
const survey = ref<RespondentSurvey | null>(null)
const credentials = ref<SessionCredentials | null>(null)
const consent = ref(false)
const loading = ref(true)
const starting = ref(false)
const declined = ref(false)
const error = ref('')

onMounted(async () => {
  const token = String(route.params.token)
  const recoveryKey = `simutu:invitation:${token}`
  try {
    const recovered = sessionStorage.getItem(recoveryKey)
    credentials.value = recovered ? (JSON.parse(recovered) as SessionCredentials) : await exchangeInvitation(token)
    sessionStorage.setItem(recoveryKey, JSON.stringify(credentials.value))
    survey.value = await getRespondentSurvey(credentials.value.session_token)
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
})

async function start() {
  if (!consent.value || !credentials.value) return
  starting.value = true
  try {
    const response = await createResponse(credentials.value)
    sessionStorage.setItem(`simutu:credentials:${response.id}`, JSON.stringify(credentials.value))
    await router.push(`/respond/responses/${response.id}`)
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    starting.value = false
  }
}

async function decline() {
  if (!credentials.value) return
  await declineParticipation(credentials.value)
  declined.value = true
}
</script>

<template>
  <main id="main-content" class="public-response-page">
    <section class="response-page" aria-labelledby="invitation-title">
      <div class="brand-lockup compact"><span class="brand-mark" aria-hidden="true">SM</span><div><strong>SIMUTU</strong><span>Undangan survei mutu</span></div></div>
      <p v-if="loading" role="status">Memeriksa undangan…</p>
      <BaseAlert v-else-if="error && !survey" tone="error" title="Undangan tidak dapat digunakan">{{ error }}</BaseAlert>
      <div v-else-if="declined" class="success-panel"><h1 id="invitation-title" tabindex="-1">Pilihan Anda tersimpan</h1><p>Tidak ada isi jawaban yang dibuat. Anda dapat menutup halaman ini.</p></div>
      <template v-else-if="survey">
        <p class="eyebrow">{{ survey.code }}</p><h1 id="invitation-title" tabindex="-1">{{ survey.name }}</h1>
        <p class="lede">{{ survey.question_count }} pertanyaan · sekitar {{ survey.estimated_minutes }} menit · partisipasi sukarela</p>
        <article class="panel invitation-notice"><h2>Pemberitahuan privasi</h2><p>{{ survey.privacy_notice }}</p><p><strong>{{ survey.privacy_mode === 'confidential' ? 'Rahasia:' : 'Anonim:' }}</strong> {{ survey.privacy_mode === 'confidential' ? 'tautan identitas disimpan terpisah dengan akses terbatas.' : 'isi jawaban tidak menyimpan tautan identitas partisipan.' }}</p><p class="fine-print">Pimpinan tidak dapat melihat jawaban individual.</p></article>
        <BaseAlert v-if="error" tone="error" title="Permintaan gagal">{{ error }}</BaseAlert>
        <div class="consent-panel panel"><label class="consent-check"><input v-model="consent" type="checkbox" /> <span>Saya telah membaca pemberitahuan dan bersedia berpartisipasi.</span></label><div class="button-row"><BaseButton :loading="starting" :disabled="!consent" @click="start">Setuju dan mulai</BaseButton><BaseButton variant="secondary" @click="decline">Saya tidak bersedia</BaseButton></div></div>
      </template>
    </section>
  </main>
</template>
