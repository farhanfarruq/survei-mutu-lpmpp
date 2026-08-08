<script setup lang="ts">
import { onMounted, ref } from 'vue'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { api, normalizeApiError } from '@/services/api'

type Health = { status: string; checks: Record<string, string> }
const health = ref<Health | null>(null)
const error = ref('')

onMounted(async () => {
  try {
    const response = await api.get<{ data: Health }>('/api/v1/health/ready')
    health.value = response.data.data
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  }
})
</script>

<template>
  <div class="page-heading"><div><p class="eyebrow">Observability baseline</p><h1 tabindex="-1">Status Sistem</h1><p class="lede">Readiness tanpa menampilkan host, credential, secret, atau detail infrastruktur sensitif.</p></div></div>
  <BaseAlert v-if="error" tone="error" title="Status tidak tersedia">{{ error }}</BaseAlert>
  <section v-else-if="health" class="kpi-grid"><article v-for="(status, component) in health.checks" :key="component" class="kpi-card"><span>{{ component }}</span><strong class="foundation-kpi-text">{{ status }}</strong><small>Readiness check</small></article></section>
  <div v-else class="skeleton-stack" role="status"><p>Memeriksa layanan…</p><span></span><span></span></div>
</template>
