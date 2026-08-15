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
const workspace = ref<AiWorkspaceOptions>({ runs: [], reviewers: [], jobs: [] })
const job = ref<AiJob | null>(null)
const result = ref<AiResult | null>(null)
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const status = ref('')
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
const promptText = ref(
  'Buat insight terstruktur hanya dari agregat yang diberikan. Jelaskan keterbatasan dan jangan menebak data.',
)
const runForm = reactive({ analysisRunId: '', configId: '', promptId: '', reviewerId: '' })
const lookupId = ref('')
const reviewNote = ref('')
const editedJson = ref('')
const selectedRun = computed(() =>
  workspace.value.runs.find((item) => item.id === runForm.analysisRunId),
)
const availableReviewers = computed(() =>
  workspace.value.reviewers.filter((item) =>
    item.unit_ids.includes(selectedRun.value?.unit_id ?? ''),
  ),
)

function selectRun() {
  runForm.reviewerId = String(availableReviewers.value[0]?.id ?? '')
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
    if (!availableReviewers.value.some((item) => item.id === Number(runForm.reviewerId)))
      runForm.reviewerId = String(availableReviewers.value[0]?.id ?? '')
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
    prompts.value.unshift(saved)
    status.value = `Prompt versi ${saved.version} dibuat.`
  })
}
async function createJob() {
  await run(async () => {
    job.value = await phase13Api.createAiJob(
      runForm.analysisRunId,
      runForm.configId,
      runForm.promptId,
      Number(runForm.reviewerId),
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
    editedJson.value = result.value ? JSON.stringify(result.value.content, null, 2) : ''
  })
}
async function decide(decision: 'edit' | 'approve' | 'reject') {
  if (!result.value) return
  await run(async () => {
    const content =
      decision === 'edit' ? (JSON.parse(editedJson.value) as Record<string, unknown>) : undefined
    result.value = await phase13Api.reviewAiResult(
      result.value!,
      decision,
      reviewNote.value,
      content,
    )
    status.value = `Keputusan ${decision} tersimpan dan diaudit.`
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
          hasil AI wajib diperiksa manusia.
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
        <label>Instruksi sistem<textarea v-model="promptText" rows="6" required></textarea></label
        ><button class="button button-secondary" :disabled="busy">Buat versi aktif</button>
        <p>{{ prompts.length }} versi tercatat dengan checksum.</p>
      </form>

      <form v-if="auth.can('ai.execute')" class="panel phase13-form" @submit.prevent="createJob">
        <h2>Buat ringkasan hasil survei</h2>
        <label
          >Hasil survei<select v-model="runForm.analysisRunId" required @change="selectRun">
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
        ><label
          >Pemeriksa independen<select v-model="runForm.reviewerId" required>
            <option value="">Pilih pemeriksa</option>
            <option v-for="item in availableReviewers" :key="item.id" :value="String(item.id)">
              {{ item.name }}
            </option>
          </select></label
        ><button class="button button-primary" :disabled="busy || !availableReviewers.length">
          Buat ringkasan AI
        </button>
        <p v-if="!workspace.runs.length">Belum ada hasil survei yang telah dirilis.</p>
        <p v-else-if="!availableReviewers.length">
          Belum ada pemeriksa independen pada unit hasil yang dipilih.
        </p>
      </form>

      <form
        v-if="auth.can('ai.read') || auth.can('ai.review')"
        class="panel phase13-form"
        @submit.prevent="lookup"
      >
        <h2>Hasil dan pemeriksaan</h2>
        <label
          >Riwayat analisis<select v-model="lookupId" required>
            <option value="">Pilih hasil</option>
            <option v-for="item in workspace.jobs" :key="item.id" :value="item.id">
              {{ item.survey }} · {{ item.unit }} · {{ item.state }}
            </option>
          </select></label
        ><button class="button button-secondary" :disabled="busy">Tampilkan hasil</button>
        <div v-if="job">
          <p>
            <strong>Status:</strong> {{ job.state }} · {{ job.review_status ?? 'belum ada hasil' }}
          </p>
          <p v-if="job.failure_code">Fallback: {{ job.failure_code }}</p>
        </div>
        <template v-if="result"
          ><dl class="definition-list">
            <div>
              <dt>Label</dt>
              <dd>{{ result.label }}</dd>
            </div>
            <div>
              <dt>Sumber</dt>
              <dd>Snapshot agregat</dd>
            </div>
            <div>
              <dt>Model</dt>
              <dd>{{ result.provider }} / {{ result.model }}</dd>
            </div>
            <div>
              <dt>Waktu</dt>
              <dd>{{ new Date(result.generated_at).toLocaleString('id-ID') }}</dd>
            </div>
          </dl>
          <pre class="phase13-json">{{ JSON.stringify(result.content, null, 2) }}</pre>
          <template v-if="auth.can('ai.review')"
            ><label>Catatan pemeriksa<textarea v-model="reviewNote" required></textarea></label
            ><label
              >Konten terstruktur untuk edit<textarea v-model="editedJson" rows="12"></textarea>
            </label>
            <div class="phase13-actions">
              <button type="button" class="button button-secondary" @click="decide('edit')">
                Simpan edit</button
              ><button type="button" class="button button-primary" @click="decide('approve')">
                Setujui</button
              ><button type="button" class="button button-danger" @click="decide('reject')">
                Tolak
              </button>
            </div></template
          ></template
        >
      </form>
    </div>
  </section>
</template>
