<script setup lang="ts">
import { onMounted, ref } from 'vue'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import { getResponseHistory, type ResponseHistory } from '@/services/responses'

const history = ref<ResponseHistory[]>([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    history.value = await getResponseHistory()
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="response-page" aria-labelledby="history-title">
    <div class="page-heading"><div><p class="eyebrow">Privasi terjaga</p><h1 id="history-title" tabindex="-1">Riwayat partisipasi</h1><p class="lede">Riwayat hanya menunjukkan status partisipasi. Isi jawaban dan receipt anonim tidak ditautkan ke akun.</p></div></div>
    <p v-if="loading" role="status">Memuat riwayat…</p>
    <BaseAlert v-else-if="error" tone="error" title="Riwayat tidak dapat dimuat">{{ error }}</BaseAlert>
    <div v-else-if="history.length" class="survey-list"><article v-for="item in history" :key="item.survey_id" class="survey-row"><div><span class="status" :class="item.status === 'completed' ? 'active' : 'neutral'">{{ item.status }}</span><h2>{{ item.survey_name }}</h2><p>{{ item.survey_code }} · {{ item.completed_at ? `selesai ${new Date(item.completed_at).toLocaleString('id-ID')}` : `tutup ${new Date(item.closes_at).toLocaleString('id-ID')}` }}</p></div></article></div>
    <div v-else class="empty-state"><h2>Belum ada riwayat</h2><p>Partisipasi Anda akan tampil di sini tanpa jawaban individual.</p></div>
  </section>
</template>
