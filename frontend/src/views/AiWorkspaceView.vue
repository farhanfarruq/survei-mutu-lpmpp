<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import {
  phase13Api,
  type AiConfig,
  type AiJob,
  type AiPrompt,
  type AiResult,
  type AiWorkspaceOptions,
} from '@/services/phase13'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const configs = ref<AiConfig[]>([])
const prompts = ref<AiPrompt[]>([])
const workspace = ref<AiWorkspaceOptions>({ runs: [], jobs: [] })
const job = ref<AiJob | null>(null)
const result = ref<AiResult | null>(null)
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const status = ref('')
const promptPresets = [
  {
    id: 'executive',
    name: 'Ringkasan eksekutif',
    description: 'Ringkasan singkat untuk pimpinan dengan fakta utama dan rekomendasi prioritas.',
    prompt:
      'Anda adalah analis mutu perguruan tinggi. Buat ringkasan eksekutif berbahasa Indonesia yang ringkas dan mudah dipahami pimpinan. Gunakan hanya data agregat yang tersedia, jelaskan capaian utama, area yang perlu perhatian, dan maksimal tiga rekomendasi yang dapat ditindaklanjuti. Sebutkan keterbatasan data dan jangan membuat sebab, angka, atau perbandingan yang tidak tersedia.',
  },
  {
    id: 'improvement',
    name: 'Prioritas peningkatan mutu',
    description: 'Menyoroti indikator terendah dan tindakan perbaikan yang paling relevan.',
    prompt:
      'Anda adalah fasilitator peningkatan mutu perguruan tinggi. Identifikasi maksimal tiga kategori atau indikator agregat yang paling membutuhkan perhatian. Jelaskan bukti skor dan jumlah respons yang tersedia, lalu berikan rekomendasi perbaikan yang realistis. Jangan menilai data yang disuppression atau kosong, bedakan fakta dari interpretasi, dan jangan menyimpulkan hubungan sebab-akibat.',
  },
  {
    id: 'trend',
    name: 'Tren dan risiko',
    description: 'Membaca perubahan dan risiko tanpa memaksakan kesimpulan saat data belum cukup.',
    prompt:
      'Anda adalah analis risiko mutu perguruan tinggi. Jelaskan tren hanya jika data periode pembanding memang tersedia. Soroti perubahan penting, potensi risiko, dan tindak lanjut pemantauan berdasarkan data agregat. Jika tren tidak dapat dihitung atau sampel terbatas, nyatakan dengan jelas sebagai keterbatasan. Jangan menebak penyebab atau membuat data baru.',
  },
  {
    id: 'comprehensive',
    name: 'Evaluasi mutu menyeluruh',
    description: 'Ulasan seimbang tentang kekuatan, kelemahan, cakupan respons, dan tindak lanjut.',
    prompt:
      'Anda adalah evaluator mutu perguruan tinggi. Buat analisis menyeluruh tetapi ringkas dari hasil agregat: kekuatan utama, area lemah, cakupan respons, pola kategori dan indikator, serta rekomendasi tindak lanjut. Gunakan bahasa Indonesia yang netral, tampilkan keterbatasan metodologis, jangan membuka data individual, dan jangan membuat klaim yang tidak didukung data.',
  },
] as const
const configForm = reactive({
  provider: 'mock',
  model: 'governed-model',
  base_url: 'https://mock.invalid',
  api_key: '',
  enabled: false,
  max_input_tokens: 8000,
  max_output_tokens: 2000,
  max_cost_micros: 100000,
  input_cost_micros_per_1k: 0,
  output_cost_micros_per_1k: 0,
  timeout_seconds: 30,
  rate_limit_per_minute: 10,
})
const selectedPromptPreset = ref(promptPresets[0].id)
const promptText = ref(promptPresets[0].prompt)
const runForm = reactive({ analysisRunId: '', configId: '', promptId: '' })
const lookupId = ref('')
type InsightContent = {
  summary: string
  topics: string[]
  sentiment: { label: string; confidence: number }
  trend_explanation: string
  recommendations: string[]
  limitations: string[]
}
const insight = computed(() => result.value?.content as unknown as InsightContent | null)
const selectedPromptDescription = computed(
  () => promptPresets.find((preset) => preset.id === selectedPromptPreset.value)?.description ?? '',
)
const sentimentLabels: Record<string, string> = {
  positive: 'Positif',
  neutral: 'Netral',
  negative: 'Negatif',
  mixed: 'Campuran',
}
const jobStateLabels: Record<string, string> = {
  queued: 'Dalam antrean',
  running: 'Sedang diproses',
  completed: 'Selesai',
  completed_with_fallback: 'Selesai dengan fallback',
  failed: 'Gagal',
}
const sentimentStatusClass = computed(() => {
  if (insight.value?.sentiment.label === 'positive') return 'active'
  if (['negative', 'mixed'].includes(insight.value?.sentiment.label ?? '')) return 'warning'
  return 'neutral'
})
const jobStatusClass = computed(() => {
  if (job.value?.state === 'completed') return 'active'
  if (job.value?.state === 'completed_with_fallback' || job.value?.state === 'failed')
    return 'warning'
  return 'neutral'
})

function applyPromptPreset() {
  const preset = promptPresets.find((item) => item.id === selectedPromptPreset.value)
  if (preset) promptText.value = preset.prompt
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    workspace.value = await phase13Api.aiWorkspaceOptions()
    if (auth.can('ai.config') || auth.can('ai.execute'))
      [configs.value, prompts.value] = await Promise.all([
        phase13Api.aiConfigs(),
        phase13Api.prompts(),
      ])
    runForm.analysisRunId ||= workspace.value.runs[0]?.id ?? ''
    runForm.configId ||= configs.value.find((item) => item.enabled)?.id ?? ''
    runForm.promptId ||= prompts.value.find((item) => item.active)?.id ?? ''
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    loading.value = false
  }
}

async function saveConfig() {
  await run(async () => {
    const saved = await phase13Api.saveAiConfig(configForm)
    configs.value = [saved, ...configs.value.filter((item) => item.id !== saved.id)]
    status.value = 'Konfigurasi tersimpan. Secret tidak ditampilkan kembali.'
  })
}
async function testConfig(item: AiConfig) {
  await run(async () => {
    const tested = await phase13Api.testAiConfig(item.id)
    status.value = `Uji koneksi: ${tested.status}. Detail provider tidak diekspos.`
    await load()
  })
}
async function savePrompt() {
  await run(async () => {
    const saved = await phase13Api.savePrompt(promptText.value)
    prompts.value = [saved, ...prompts.value.map((item) => ({ ...item, active: false }))]
    runForm.promptId = saved.id
    status.value = `Prompt versi ${saved.version} dibuat.`
  })
}
async function createJob() {
  await run(async () => {
    job.value = await phase13Api.createAiJob(
      runForm.analysisRunId,
      runForm.configId,
      runForm.promptId,
    )
    lookupId.value = job.value.id
    status.value = 'Analisis AI sedang diproses.'
    await load()
  })
}
async function lookup() {
  await run(async () => {
    job.value = await phase13Api.aiJob(lookupId.value)
    result.value = job.value.result_id ? await phase13Api.aiResult(job.value.result_id) : null
  })
}
async function run(callback: () => Promise<void>) {
  busy.value = true
  error.value = ''
  status.value = ''
  try {
    await callback()
  } catch (caught) {
    error.value = normalizeApiError(caught).message
  } finally {
    busy.value = false
  }
}

onMounted(async () => {
  await load()
  if (typeof route.query.job === 'string') {
    lookupId.value = route.query.job
    await lookup()
  }
})
</script>

<template>
  <section aria-labelledby="ai-title">
    <div class="page-heading">
      <div>
        <p class="eyebrow">Insight terkelola</p>
        <h1 id="ai-title" tabindex="-1">Analisis AI</h1>
        <p>
          AI hanya membantu merangkum hasil agregat. Statistik utama tetap dihitung oleh sistem dan
          hasilnya perlu dibaca ulang sebelum digunakan sebagai dasar keputusan.
        </p>
      </div>
    </div>
    <p v-if="loading" role="status">Memuat workspace AI…</p>
    <BaseAlert v-if="error" tone="error" title="Operasi AI gagal">{{ error }}</BaseAlert>
    <p v-if="status" class="inline-success" role="status">{{ status }}</p>

    <div v-if="!loading" class="phase13-grid">
      <form v-if="auth.can('ai.config')" class="panel phase13-form" @submit.prevent="saveConfig">
        <h2>Konfigurasi layanan AI</h2>
        <p>
          Isi satu layanan yang telah disetujui TIK. API key disimpan terenkripsi dan tidak akan
          ditampilkan kembali.
        </p>
        <label>Nama provider<input v-model="configForm.provider" required /></label
        ><label>Nama model<input v-model="configForm.model" required /></label
        ><label
          >Alamat API yang diizinkan<input
            v-model="configForm.base_url"
            type="url"
            required /></label
        ><label
          >API key<input v-model="configForm.api_key" type="password" autocomplete="new-password"
        /></label>
        <details>
          <summary>Batas penggunaan lanjutan</summary>
          <div class="phase13-fields">
            <label
              >Maks. input token<input
                v-model.number="configForm.max_input_tokens"
                type="number"
                min="128" /></label
            ><label
              >Maks. output token<input
                v-model.number="configForm.max_output_tokens"
                type="number"
                min="64" /></label
            ><label
              >Timeout detik<input
                v-model.number="configForm.timeout_seconds"
                type="number"
                min="1" /></label
            ><label
              >Permintaan per menit<input
                v-model.number="configForm.rate_limit_per_minute"
                type="number"
                min="1"
            /></label>
          </div>
        </details>
        <label class="checkbox-label"
          ><input v-model="configForm.enabled" type="checkbox" /> Aktifkan provider/model</label
        ><button class="button button-primary" :disabled="busy">Simpan aman</button>
        <ul class="compact-list">
          <li v-for="item in configs" :key="item.id">
            <strong>{{ item.provider }} / {{ item.model }}</strong> ·
            {{ item.secret_masked ?? 'tanpa secret' }} · {{ item.connection_status }}
            <button type="button" class="button button-quiet" @click="testConfig(item)">Uji</button>
          </li>
        </ul>
      </form>

      <form v-if="auth.can('ai.config')" class="panel phase13-form" @submit.prevent="savePrompt">
        <h2>Versi prompt</h2>
        <label
          >Template analisis<select v-model="selectedPromptPreset" @change="applyPromptPreset">
            <option v-for="preset in promptPresets" :key="preset.id" :value="preset.id">
              {{ preset.name }}
            </option>
          </select></label
        >
        <p>{{ selectedPromptDescription }}</p>
        <label>Instruksi sistem<textarea v-model="promptText" rows="6" required></textarea></label
        ><small
          >Format output dijaga otomatis oleh sistem; fokuskan prompt pada tujuan analisis.</small
        ><button class="button button-secondary" :disabled="busy">Buat versi aktif</button>
        <p>{{ prompts.length }} versi tercatat dengan checksum.</p>
      </form>

      <form v-if="auth.can('ai.execute')" class="panel phase13-form" @submit.prevent="createJob">
        <h2>Buat ringkasan hasil survei</h2>
        <label
          >Hasil survei<select v-model="runForm.analysisRunId" required>
            <option value="">Pilih hasil yang telah dirilis</option>
            <option v-for="item in workspace.runs" :key="item.id" :value="item.id">
              {{ item.survey }} · {{ item.unit }} · {{ item.period }}
            </option>
          </select></label
        ><label
          >Provider<select v-model="runForm.configId" required>
            <option value="">Pilih</option>
            <option
              v-for="item in configs.filter((row) => row.enabled)"
              :key="item.id"
              :value="item.id"
            >
              {{ item.provider }} / {{ item.model }}
            </option>
          </select></label
        ><label
          >Petunjuk analisis<select v-model="runForm.promptId" required>
            <option value="">Pilih</option>
            <option
              v-for="item in prompts.filter((row) => row.active)"
              :key="item.id"
              :value="item.id"
            >
              Versi {{ item.version }}
            </option>
          </select></label
        ><button class="button button-primary" :disabled="busy">Buat ringkasan AI</button>
        <p v-if="!workspace.runs.length">Belum ada hasil survei yang telah dirilis.</p>
      </form>

      <form
        v-if="auth.can('ai.read')"
        class="panel phase13-form ai-result-panel"
        @submit.prevent="lookup"
      >
        <div>
          <p class="eyebrow">Insight survei</p>
          <h2>Hasil ringkasan AI</h2>
        </div>
        <div class="ai-result-controls">
          <label
            >Riwayat analisis<select v-model="lookupId" required>
              <option value="">Pilih hasil</option>
              <option v-for="item in workspace.jobs" :key="item.id" :value="item.id">
                {{ item.survey }} · {{ item.unit }} ·
                {{ jobStateLabels[item.state] ?? item.state }}
              </option>
            </select></label
          ><button class="button button-secondary" :disabled="busy">Tampilkan hasil</button>
        </div>
        <div v-if="job" class="ai-job-status" role="status">
          <div>
            <span class="status" :class="jobStatusClass">
              {{ jobStateLabels[job.state] ?? job.state }}
            </span>
            <span>Proses ringkasan AI</span>
          </div>
          <p v-if="job.failure_code">Fallback digunakan: {{ job.failure_code }}</p>
        </div>
        <template v-if="result">
          <dl class="ai-result-meta" aria-label="Informasi hasil AI">
            <div>
              <dt>Status keluaran</dt>
              <dd>{{ result.label }}</dd>
            </div>
            <div>
              <dt>Sumber data</dt>
              <dd>Snapshot agregat</dd>
            </div>
            <div>
              <dt>Provider dan model</dt>
              <dd>{{ result.provider }} / {{ result.model }}</dd>
            </div>
            <div>
              <dt>Dibuat pada</dt>
              <dd class="tabular-nums">
                {{ new Date(result.generated_at).toLocaleString('id-ID') }}
              </dd>
            </div>
          </dl>

          <article v-if="insight" class="ai-insight" aria-labelledby="ai-summary-title">
            <section class="ai-insight-summary">
              <p class="eyebrow">Intisari analisis</p>
              <h3 id="ai-summary-title">Ringkasan utama</h3>
              <p>{{ insight.summary }}</p>
            </section>

            <div class="ai-insight-grid">
              <section class="ai-insight-card">
                <h3>Topik utama</h3>
                <ul v-if="insight.topics.length" class="ai-topic-list">
                  <li v-for="topic in insight.topics" :key="topic">{{ topic }}</li>
                </ul>
                <p v-else>Tidak ada topik khusus yang teridentifikasi.</p>
              </section>

              <section class="ai-insight-card">
                <div class="ai-card-heading">
                  <h3>Sentimen</h3>
                  <span class="status" :class="sentimentStatusClass">
                    {{ sentimentLabels[insight.sentiment.label] ?? insight.sentiment.label }}
                  </span>
                </div>
                <div class="progress-label">
                  <span>Tingkat keyakinan</span>
                  <strong class="tabular-nums">
                    {{ Math.round(insight.sentiment.confidence * 100) }}%
                  </strong>
                </div>
                <progress
                  :value="Math.round(insight.sentiment.confidence * 100)"
                  max="100"
                  aria-label="Tingkat keyakinan sentimen"
                ></progress>
              </section>

              <section class="ai-insight-card ai-insight-wide">
                <h3>Penjelasan tren</h3>
                <p>{{ insight.trend_explanation }}</p>
              </section>

              <section class="ai-insight-card ai-insight-wide">
                <h3>Rekomendasi tindak lanjut</h3>
                <ol v-if="insight.recommendations.length" class="ai-recommendation-list">
                  <li v-for="recommendation in insight.recommendations" :key="recommendation">
                    {{ recommendation }}
                  </li>
                </ol>
                <p v-else>Belum ada rekomendasi.</p>
              </section>

              <section class="ai-insight-card ai-insight-wide ai-limitations">
                <h3>Keterbatasan analisis</h3>
                <ul v-if="insight.limitations.length" class="ai-limitation-list">
                  <li v-for="limitation in insight.limitations" :key="limitation">
                    {{ limitation }}
                  </li>
                </ul>
                <p v-else>Tidak ada keterbatasan tambahan yang dinyatakan.</p>
              </section>
            </div>
          </article>
        </template>
      </form>
    </div>
  </section>
</template>
