<script setup lang="ts">
import { onMounted, ref } from 'vue'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import { listEligibleSurveys, type EligibleSurvey } from '@/services/responses'

const surveys = ref<EligibleSurvey[]>([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    surveys.value = await listEligibleSurveys()
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="response-page" aria-labelledby="eligible-title">
    <div class="page-heading">
      <div><p class="eyebrow">Pengumpulan respons</p><h1 id="eligible-title" tabindex="-1">Survei yang dapat diisi</h1><p class="lede">Daftar ini hanya memuat campaign aktif yang sesuai dengan kelompok atau unit Anda.</p></div>
      <RouterLink class="button button-secondary" to="/app/response-history">Riwayat partisipasi</RouterLink>
    </div>
    <p v-if="loading" role="status">Memuat survei…</p>
    <BaseAlert v-else-if="error" tone="error" title="Survei tidak dapat dimuat">{{ error }}</BaseAlert>
    <div v-else-if="surveys.length" class="survey-list">
      <article v-for="survey in surveys" :key="survey.id" class="survey-row">
        <div>
          <div class="badge-row"><span class="status active">Aktif</span><span class="status neutral">{{ survey.privacy_mode === 'confidential' ? 'Rahasia' : 'Anonim' }}</span></div>
          <h2>{{ survey.name }}</h2>
          <p>{{ survey.question_count }} pertanyaan · sekitar {{ survey.estimated_minutes }} menit · tutup {{ new Date(survey.closes_at).toLocaleString('id-ID') }}</p>
        </div>
        <RouterLink class="button button-primary" :to="`/app/surveys/${survey.id}`">{{ survey.participation_status === 'eligible' ? 'Lihat detail' : 'Sudah dimulai' }}</RouterLink>
      </article>
    </div>
    <div v-else class="empty-state"><h2>Belum ada survei aktif</h2><p>Survei akan tampil saat campaign sesuai scope Anda dibuka.</p></div>
  </section>
</template>
