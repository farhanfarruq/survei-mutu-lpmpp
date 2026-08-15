<script setup lang="ts">
import { BarChart, LineChart, PieChart, RadarChart } from 'echarts/charts'
import {
  GridComponent,
  LegendComponent,
  RadarComponent,
  TooltipComponent,
} from 'echarts/components'
import { use } from 'echarts/core'
import { SVGRenderer } from 'echarts/renderers'
import { computed, onMounted, ref } from 'vue'
import VChart from 'vue-echarts'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import {
  fetchLeadershipDashboard,
  requestReportExport,
  type DashboardSeries,
  type LeadershipDashboard,
} from '@/services/analytics'
import { useAuthStore } from '@/stores/auth'

use([
  BarChart,
  LineChart,
  PieChart,
  RadarChart,
  GridComponent,
  LegendComponent,
  RadarComponent,
  TooltipComponent,
  SVGRenderer,
])

const auth = useAuthStore()
const dashboard = ref<LeadershipDashboard | null>(null)
const baselineSeries = ref<DashboardSeries[]>([])
const loading = ref(true)
const error = ref('')
const unitId = ref('')
const periodId = ref('')
const surveyId = ref('')
const groupId = ref('')
const categoryCode = ref('')
const questionId = ref('')
const exportFormat = ref<'csv' | 'json'>('csv')
const exportStatus = ref('')
const categoryChartType = ref<'bar' | 'radar'>('bar')
const answerChartType = ref<'bar' | 'donut'>('bar')

const units = computed(() => [
  ...new Map(
    baselineSeries.value.map((row) => [row.unit_id, { id: row.unit_id, name: row.unit }]),
  ).values(),
])
const periods = computed(() => [
  ...new Map(
    baselineSeries.value.map((row) => [row.period_id, { id: row.period_id, name: row.period }]),
  ).values(),
])
const surveys = computed(() => [
  ...new Map(
    baselineSeries.value.map((row) => [row.survey_id, { id: row.survey_id, name: row.survey }]),
  ).values(),
])
const groups = computed(() => [
  ...new Map(
    baselineSeries.value
      .filter((row) => row.group_id)
      .map((row) => [row.group_id, { id: row.group_id!, name: row.group! }]),
  ).values(),
])
const categories = computed(() => dashboard.value?.summary?.categories ?? [])
const displayedCategories = computed(() =>
  categoryCode.value
    ? categories.value.filter((row) => row.code === categoryCode.value)
    : categories.value,
)
const displayedItems = computed(() =>
  (dashboard.value?.drilldown ?? []).filter(
    (row) => !categoryCode.value || row.category_code === categoryCode.value,
  ),
)
const selectedItem = computed(
  () =>
    displayedItems.value.find((row) => row.id === questionId.value) ??
    displayedItems.value[0] ??
    null,
)
const canUseRadar = computed(() => displayedCategories.value.length >= 3)
const categoryChart = computed(() => {
  const names = displayedCategories.value.map((row) => row.name ?? row.code)
  const scores = displayedCategories.value.map((row) => row.normalized_score ?? 0)

  if (categoryChartType.value === 'radar' && canUseRadar.value) {
    return {
      aria: { enabled: true, decal: { show: true } },
      tooltip: { trigger: 'item' },
      radar: { indicator: names.map((name) => ({ name, max: 100 })), radius: '66%' },
      series: [{ type: 'radar', data: [{ name: 'Skor kategori', value: scores }] }],
    }
  }

  return {
    aria: { enabled: true, decal: { show: true } },
    tooltip: { trigger: 'axis' },
    grid: { left: 18, right: 24, top: 18, bottom: 18, containLabel: true },
    xAxis: { type: 'value', min: 0, max: 100, name: 'Skor 0–100' },
    yAxis: { type: 'category', data: names },
    series: [{ type: 'bar', data: scores, color: '#155f82' }],
  }
})
const trendChart = computed(() => ({
  aria: { enabled: true, decal: { show: true } },
  tooltip: { trigger: 'axis' },
  grid: { left: 18, right: 24, top: 18, bottom: 36, containLabel: true },
  xAxis: { type: 'category', data: dashboard.value?.trend.series.map((row) => row.period) ?? [] },
  yAxis: { type: 'value', min: 0, max: 100, name: 'Skor' },
  series: [
    {
      type: 'line',
      smooth: false,
      data: dashboard.value?.trend.series.map((row) => row.score) ?? [],
      color: '#b44a35',
    },
  ],
}))
const answerChart = computed(() => {
  const distribution = selectedItem.value?.distribution ?? []

  if (answerChartType.value === 'donut') {
    return {
      aria: { enabled: true, decal: { show: true } },
      tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
      legend: { bottom: 0 },
      series: [
        {
          type: 'pie',
          radius: ['42%', '68%'],
          data: distribution.map((row) => ({
            name: row.label ?? String(row.value),
            value: row.count,
          })),
        },
      ],
    }
  }

  return {
    aria: { enabled: true, decal: { show: true } },
    tooltip: { trigger: 'axis' },
    grid: { left: 18, right: 24, top: 18, bottom: 64, containLabel: true },
    xAxis: {
      type: 'category',
      axisLabel: { interval: 0, rotate: 20 },
      data: distribution.map((row) => row.label ?? String(row.value)),
    },
    yAxis: { type: 'value', min: 0, name: 'Jumlah jawaban' },
    series: [{ type: 'bar', data: distribution.map((row) => row.count), color: '#155f82' }],
  }
})

function changeCategory(event: Event) {
  categoryCode.value = (event.target as HTMLSelectElement).value
  if (categoryCode.value) categoryChartType.value = 'bar'
  questionId.value = displayedItems.value[0]?.id ?? ''
}

async function load(preserveOptions = true) {
  loading.value = true
  error.value = ''
  try {
    const result = await fetchLeadershipDashboard({
      ...(unitId.value && { unit_id: unitId.value }),
      ...(periodId.value && { period_id: periodId.value }),
      ...(surveyId.value && { survey_id: surveyId.value }),
      ...(groupId.value && { group_id: groupId.value }),
      drilldown: 'item' as const,
    })
    dashboard.value = result
    if (preserveOptions && baselineSeries.value.length === 0)
      baselineSeries.value = result.comparison.series
    if (!result.summary?.categories.some((row) => row.code === categoryCode.value))
      categoryCode.value = ''
    questionId.value =
      result.drilldown.find(
        (row) => !categoryCode.value || row.category_code === categoryCode.value,
      )?.id ?? ''
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
    exportStatus.value =
      result.state === 'completed'
        ? 'Ekspor siap. Buka pusat unduhan untuk membuat tiket sekali pakai.'
        : 'Ekspor masuk antrean.'
  } catch (caught) {
    exportStatus.value = normalizeApiError(caught).message
  }
}

onMounted(() => load())
</script>

<template>
  <section class="analytics-page" aria-labelledby="analytics-title">
    <div class="page-heading">
      <div>
        <p class="eyebrow">Analitik survei</p>
        <h1 id="analytics-title" tabindex="-1">Dashboard hasil survei</h1>
        <p class="lede">
          Pilih survei dan kategori untuk melihat skor, rata-rata, serta distribusi jawaban anonim
          yang telah memenuhi ambang privasi.
        </p>
      </div>
      <div v-if="auth.can('report.export')" class="export-controls">
        <label
          >Format<select v-model="exportFormat">
            <option value="csv">CSV</option>
            <option value="json">JSON</option>
          </select></label
        ><button
          class="button button-secondary"
          :disabled="!dashboard?.summary"
          @click="exportReport"
        >
          Buat ekspor
        </button>
      </div>
    </div>

    <form
      class="analytics-filters panel"
      aria-label="Filter dashboard"
      @submit.prevent="load(false)"
    >
      <label
        >Unit<select v-model="unitId">
          <option value="">Semua unit dalam scope</option>
          <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
        </select></label
      >
      <label
        >Periode<select v-model="periodId">
          <option value="">Semua periode</option>
          <option v-for="period in periods" :key="period.id" :value="period.id">
            {{ period.name }}
          </option>
        </select></label
      >
      <label
        >Survei<select v-model="surveyId">
          <option value="">Semua survei</option>
          <option v-for="survey in surveys" :key="survey.id" :value="survey.id">
            {{ survey.name }}
          </option>
        </select></label
      >
      <label
        >Grup responden<select v-model="groupId">
          <option value="">Semua grup agregat</option>
          <option v-for="group in groups" :key="group.id" :value="group.id">
            {{ group.name }}
          </option>
        </select></label
      >
      <label
        >Kategori<select :value="categoryCode" @change="changeCategory">
          <option value="">Semua kategori</option>
          <option v-for="category in categories" :key="category.code" :value="category.code">
            {{ category.name ?? category.code }}
          </option>
        </select></label
      >
      <button class="button button-primary" type="submit">Terapkan filter</button>
    </form>
    <p v-if="exportStatus" role="status">{{ exportStatus }}</p>
    <p v-if="loading" role="status">Memuat hasil agregat…</p>
    <BaseAlert v-else-if="error" tone="error" title="Dashboard tidak dapat dimuat"
      >{{ error }}
      <button class="button button-secondary" @click="load(false)">Coba lagi</button></BaseAlert
    >
    <div v-else-if="!dashboard?.summary" class="empty-state">
      <h2>Belum ada hasil released</h2>
      <p>Ubah filter atau tunggu snapshot analisis dirilis oleh pemeriksa yang berwenang.</p>
    </div>

    <template v-else>
      <p class="sr-only" aria-live="polite">{{ dashboard.accessible_summary }}</p>
      <section class="kpi-grid" aria-label="Ringkasan utama">
        <article class="kpi-card">
          <span>Skor keseluruhan</span
          ><strong>{{ dashboard.summary.overall.normalized_score ?? 'Disembunyikan' }}</strong
          ><small>{{ dashboard.summary.overall.interpretation ?? 'Di bawah threshold' }}</small>
        </article>
        <article class="kpi-card">
          <span>Response rate</span
          ><strong>{{
            dashboard.summary.response_rate.percentage === null
              ? '—'
              : `${dashboard.summary.response_rate.percentage}%`
          }}</strong
          ><small
            >{{ dashboard.summary.response_rate.submitted }} dari
            {{ dashboard.summary.response_rate.eligible }} eligible</small
          >
        </article>
        <article class="kpi-card">
          <span>Survei</span
          ><strong class="analytics-kpi-text">{{ dashboard.summary.survey }}</strong
          ><small
            >Diperbarui
            {{ new Date(dashboard.summary.last_updated_at).toLocaleDateString('id-ID') }}</small
          >
        </article>
        <article class="kpi-card">
          <span>Cakupan</span
          ><strong class="analytics-kpi-text">{{ dashboard.summary.unit }}</strong
          ><small>{{ dashboard.summary.period }}</small>
        </article>
      </section>

      <BaseAlert
        v-for="limitation in dashboard.summary.limitations"
        :key="limitation"
        tone="info"
        title="Catatan interpretasi"
        >{{ limitation }}</BaseAlert
      >

      <div class="analytics-grid">
        <section class="panel" aria-labelledby="category-chart-title">
          <div class="section-heading">
            <h2 id="category-chart-title">Skor per kategori</h2>
            <label
              >Tampilan<select v-model="categoryChartType">
                <option value="bar">Batang</option>
                <option value="radar" :disabled="!canUseRadar">Radar (minimal 3 kategori)</option>
              </select></label
            >
          </div>
          <VChart class="analytics-chart" :option="categoryChart" autoresize />
        </section>
        <section class="panel" aria-labelledby="trend-chart-title">
          <h2 id="trend-chart-title">Tren periode</h2>
          <VChart
            v-if="dashboard.trend.allowed"
            class="analytics-chart"
            :option="trendChart"
            autoresize
          />
          <p v-else>
            Tren tidak ditampilkan: setiap sel perbandingan harus memiliki minimal 30 respons dan
            instrumen yang sebanding.
          </p>
        </section>
      </div>

      <section class="panel table-panel" aria-labelledby="category-table-title">
        <h2 id="category-table-title">Tabel pendukung kategori</h2>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th scope="col">Kategori</th>
                <th scope="col">n valid</th>
                <th scope="col">Missing</th>
                <th scope="col">Skor</th>
                <th scope="col">Top-two box</th>
                <th scope="col">Interpretasi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in displayedCategories" :key="row.code">
                <th scope="row">{{ row.name }}</th>
                <td>{{ row.n }}</td>
                <td>{{ row.missing }}</td>
                <td>{{ row.normalized_score ?? 'Suppressed' }}</td>
                <td>
                  {{
                    row.top_two_box === null || row.top_two_box === undefined
                      ? '—'
                      : `${row.top_two_box}%`
                  }}
                </td>
                <td>{{ row.interpretation ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel question-detail" aria-labelledby="answer-detail-title">
        <h2 id="answer-detail-title">Detail jawaban per pertanyaan</h2>
        <p>
          Jawaban ditampilkan sebagai hitungan agregat. Sistem tidak menampilkan identitas maupun
          rangkaian jawaban milik satu responden.
        </p>
        <label v-if="displayedItems.length" class="question-selector"
          >Pertanyaan<select v-model="questionId">
            <option v-for="item in displayedItems" :key="item.id ?? item.code" :value="item.id">
              {{ item.code }} — {{ item.text }}
            </option>
          </select></label
        >
        <div v-if="selectedItem" class="kpi-grid four">
          <article class="kpi-card">
            <span>Jawaban valid</span><strong>{{ selectedItem.n }}</strong
            ><small>Respons yang dihitung</small>
          </article>
          <article class="kpi-card">
            <span>Tidak dijawab</span><strong>{{ selectedItem.missing }}</strong
            ><small>Missing</small>
          </article>
          <article class="kpi-card">
            <span>Rata-rata</span><strong>{{ selectedItem.mean ?? '—' }}</strong
            ><small>{{ selectedItem.category_name }}</small>
          </article>
          <article class="kpi-card">
            <span>Skor</span><strong>{{ selectedItem.normalized_score ?? '—' }}</strong
            ><small>Skala 0–100</small>
          </article>
        </div>
        <div v-if="selectedItem?.distribution?.length" class="question-detail-grid">
          <section aria-labelledby="answer-chart-title">
            <div class="section-heading">
              <h3 id="answer-chart-title">Grafik distribusi jawaban</h3>
              <label
                >Tampilan<select v-model="answerChartType">
                  <option value="bar">Batang</option>
                  <option value="donut">Donut</option>
                </select></label
              >
            </div>
            <VChart class="analytics-chart" :option="answerChart" autoresize />
          </section>
          <section class="table-panel" aria-labelledby="anonymous-answer-title">
            <h3 id="anonymous-answer-title">Semua jawaban anonim</h3>
            <div class="table-scroll">
              <table>
                <thead>
                  <tr>
                    <th scope="col">Pilihan jawaban</th>
                    <th scope="col">Jumlah</th>
                    <th scope="col">Persentase</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in selectedItem.distribution" :key="String(row.value)">
                    <th scope="row">{{ row.label ?? row.value }}</th>
                    <td>{{ row.count }}</td>
                    <td>{{ row.percentage }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
        <BaseAlert v-else-if="selectedItem" tone="info" title="Distribusi belum tersedia"
          >Snapshot ini hanya memiliki skor dan rata-rata. Jalankan analisis terbaru untuk
          memperoleh distribusi jawaban.</BaseAlert
        >
        <div v-else class="empty-state">
          <h3>Tidak ada detail pertanyaan</h3>
          <p>Pilih kategori lain atau pastikan snapshot analisis sudah dirilis.</p>
        </div>
      </section>

      <section class="panel table-panel" aria-labelledby="item-table-title">
        <h2 id="item-table-title">Ringkasan seluruh pertanyaan</h2>
        <p>Daftar ini tetap mengikuti survei dan kategori yang dipilih.</p>
        <div class="table-scroll">
          <table>
            <thead>
              <tr>
                <th scope="col">Pertanyaan</th>
                <th scope="col">Kategori</th>
                <th scope="col">n</th>
                <th scope="col">Missing</th>
                <th scope="col">Rata-rata</th>
                <th scope="col">Skor</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in displayedItems" :key="row.id ?? row.code">
                <th scope="row">{{ row.code }} — {{ row.text }}</th>
                <td>{{ row.category_name }}</td>
                <td>{{ row.n }}</td>
                <td>{{ row.missing }}</td>
                <td>{{ row.mean ?? '—' }}</td>
                <td>{{ row.normalized_score ?? 'Disembunyikan' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </section>
</template>
