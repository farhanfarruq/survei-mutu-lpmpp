<script setup lang="ts">
import { BarChart, LineChart } from 'echarts/charts'
import { GridComponent, TooltipComponent } from 'echarts/components'
import { use } from 'echarts/core'
import { SVGRenderer } from 'echarts/renderers'
import { computed, onMounted, ref } from 'vue'
import VChart from 'vue-echarts'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import { fetchLeadershipDashboard, requestReportExport, type DashboardSeries, type LeadershipDashboard } from '@/services/analytics'
import { useAuthStore } from '@/stores/auth'

use([BarChart, LineChart, GridComponent, TooltipComponent, SVGRenderer])

const auth = useAuthStore()
const dashboard = ref<LeadershipDashboard | null>(null)
const baselineSeries = ref<DashboardSeries[]>([])
const loading = ref(true)
const error = ref('')
const unitId = ref('')
const periodId = ref('')
const groupId = ref('')
const showItems = ref(false)
const exportFormat = ref<'csv' | 'json'>('csv')
const exportStatus = ref('')

const units = computed(() => [...new Map(baselineSeries.value.map((row) => [row.unit_id, { id: row.unit_id, name: row.unit }])).values()])
const periods = computed(() => [...new Map(baselineSeries.value.map((row) => [row.period_id, { id: row.period_id, name: row.period }])).values()])
const groups = computed(() => [...new Map(baselineSeries.value.filter((row) => row.group_id).map((row) => [row.group_id, { id: row.group_id!, name: row.group! }])).values()])
const categoryChart = computed(() => ({
  aria: { enabled: true, decal: { show: true } },
  tooltip: { trigger: 'axis' },
  grid: { left: 18, right: 24, top: 18, bottom: 18, containLabel: true },
  xAxis: { type: 'value', min: 0, max: 100, name: 'Skor 0–100' },
  yAxis: { type: 'category', data: dashboard.value?.summary?.categories.map((row) => row.name ?? row.code) ?? [] },
  series: [{ type: 'bar', data: dashboard.value?.summary?.categories.map((row) => row.normalized_score) ?? [], color: '#155f82' }],
}))
const trendChart = computed(() => ({
  aria: { enabled: true, decal: { show: true } },
  tooltip: { trigger: 'axis' },
  grid: { left: 18, right: 24, top: 18, bottom: 36, containLabel: true },
  xAxis: { type: 'category', data: dashboard.value?.trend.series.map((row) => row.period) ?? [] },
  yAxis: { type: 'value', min: 0, max: 100, name: 'Skor' },
  series: [{ type: 'line', smooth: false, data: dashboard.value?.trend.series.map((row) => row.score) ?? [], color: '#b44a35' }],
}))

async function load(preserveOptions = true) {
  loading.value = true
  error.value = ''
  try {
    const result = await fetchLeadershipDashboard({
      ...(unitId.value && { unit_id: unitId.value }),
      ...(periodId.value && { period_id: periodId.value }),
      ...(groupId.value && { group_id: groupId.value }),
      ...(showItems.value && { drilldown: 'item' as const }),
    })
    dashboard.value = result
    if (preserveOptions && baselineSeries.value.length === 0) baselineSeries.value = result.comparison.series
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
}

async function exportReport() {
  const snapshotId = dashboard.value?.comparison.series[0]?.snapshot_id
  if (!snapshotId) return
  exportStatus.value = 'Menyiapkan ekspor…'
  try {
    const result = await requestReportExport(snapshotId, exportFormat.value)
    exportStatus.value = result.state === 'completed' ? 'Ekspor siap. Buka pusat unduhan untuk membuat tiket sekali pakai.' : 'Ekspor masuk antrean.'
  } catch (caught) {
    exportStatus.value = normalizeApiError(caught).message
  }
}

onMounted(() => load())
</script>

<template>
  <section class="analytics-page" aria-labelledby="analytics-title">
    <div class="page-heading">
      <div><p class="eyebrow">Analytics & reporting</p><h1 id="analytics-title" tabindex="-1">Dashboard eksekutif</h1><p class="lede">Hanya agregat released dalam scope organisasi Anda. Jawaban individual tidak tersedia di halaman ini.</p></div>
      <div v-if="auth.can('report.export')" class="export-controls"><label>Format<select v-model="exportFormat"><option value="csv">CSV</option><option value="json">JSON</option></select></label><button class="button button-secondary" :disabled="!dashboard?.summary" @click="exportReport">Buat ekspor</button></div>
    </div>

    <form class="analytics-filters panel" aria-label="Filter dashboard" @submit.prevent="load(false)">
      <label>Unit<select v-model="unitId"><option value="">Semua unit dalam scope</option><option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option></select></label>
      <label>Periode<select v-model="periodId"><option value="">Semua periode</option><option v-for="period in periods" :key="period.id" :value="period.id">{{ period.name }}</option></select></label>
      <label>Grup responden<select v-model="groupId"><option value="">Semua grup agregat</option><option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option></select></label>
      <label class="checkbox-label"><input v-model="showItems" type="checkbox"> Tampilkan drill-down item agregat</label>
      <button class="button button-primary" type="submit">Terapkan filter</button>
    </form>
    <p v-if="exportStatus" role="status">{{ exportStatus }}</p>
    <p v-if="loading" role="status">Memuat hasil agregat…</p>
    <BaseAlert v-else-if="error" tone="error" title="Dashboard tidak dapat dimuat">{{ error }} <button class="button button-secondary" @click="load(false)">Coba lagi</button></BaseAlert>
    <div v-else-if="!dashboard?.summary" class="empty-state"><h2>Belum ada hasil released</h2><p>Ubah filter atau tunggu snapshot analisis dirilis oleh pemeriksa yang berwenang.</p></div>

    <template v-else>
      <p class="sr-only" aria-live="polite">{{ dashboard.accessible_summary }}</p>
      <section class="kpi-grid" aria-label="Ringkasan utama">
        <article class="kpi-card"><span>Skor keseluruhan</span><strong>{{ dashboard.summary.overall.normalized_score ?? 'Disembunyikan' }}</strong><small>{{ dashboard.summary.overall.interpretation ?? 'Di bawah threshold' }}</small></article>
        <article class="kpi-card"><span>Response rate</span><strong>{{ dashboard.summary.response_rate.percentage === null ? '—' : `${dashboard.summary.response_rate.percentage}%` }}</strong><small>{{ dashboard.summary.response_rate.submitted }} dari {{ dashboard.summary.response_rate.eligible }} eligible</small></article>
        <article class="kpi-card"><span>Cakupan</span><strong class="analytics-kpi-text">{{ dashboard.summary.unit }}</strong><small>{{ dashboard.summary.period }}</small></article>
        <article class="kpi-card"><span>Terakhir diperbarui</span><strong class="analytics-kpi-text">{{ new Date(dashboard.summary.last_updated_at).toLocaleDateString('id-ID') }}</strong><small>{{ new Date(dashboard.summary.last_updated_at).toLocaleTimeString('id-ID') }}</small></article>
      </section>

      <BaseAlert v-for="limitation in dashboard.summary.limitations" :key="limitation" tone="info" title="Catatan interpretasi">{{ limitation }}</BaseAlert>

      <div class="analytics-grid">
        <section class="panel" aria-labelledby="category-chart-title"><h2 id="category-chart-title">Skor per kategori</h2><VChart class="analytics-chart" :option="categoryChart" autoresize /></section>
        <section class="panel" aria-labelledby="trend-chart-title"><h2 id="trend-chart-title">Tren periode</h2><VChart v-if="dashboard.trend.allowed" class="analytics-chart" :option="trendChart" autoresize /><p v-else>Tren tidak ditampilkan: setiap sel perbandingan harus memiliki minimal 30 respons dan instrumen yang sebanding.</p></section>
      </div>

      <section class="panel table-panel" aria-labelledby="category-table-title">
        <h2 id="category-table-title">Tabel pendukung kategori</h2>
        <div class="table-scroll"><table><thead><tr><th scope="col">Kategori</th><th scope="col">n valid</th><th scope="col">Missing</th><th scope="col">Skor</th><th scope="col">Top-two box</th><th scope="col">Interpretasi</th></tr></thead><tbody><tr v-for="row in dashboard.summary.categories" :key="row.code"><th scope="row">{{ row.name }}</th><td>{{ row.n }}</td><td>{{ row.missing }}</td><td>{{ row.normalized_score ?? 'Suppressed' }}</td><td>{{ row.top_two_box === null || row.top_two_box === undefined ? '—' : `${row.top_two_box}%` }}</td><td>{{ row.interpretation ?? '—' }}</td></tr></tbody></table></div>
      </section>

      <section v-if="showItems" class="panel table-panel" aria-labelledby="item-table-title">
        <h2 id="item-table-title">Drill-down item agregat</h2><p>Tidak memuat identitas atau jawaban responden.</p>
        <div class="table-scroll"><table><thead><tr><th scope="col">Item</th><th scope="col">n</th><th scope="col">Missing</th><th scope="col">Mean</th><th scope="col">Skor</th></tr></thead><tbody><tr v-for="row in dashboard.drilldown" :key="row.code"><th scope="row">{{ row.code }} — {{ row.text }}</th><td>{{ row.n }}</td><td>{{ row.missing }}</td><td>{{ row.mean ?? 'Suppressed' }}</td><td>{{ row.normalized_score ?? 'Suppressed' }}</td></tr></tbody></table></div>
      </section>
    </template>
  </section>
</template>
