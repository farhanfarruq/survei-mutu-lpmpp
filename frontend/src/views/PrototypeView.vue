<script setup lang="ts">
import {
  AlertTriangle,
  BarChart3,
  Bot,
  Check,
  ClipboardCheck,
  ClipboardList,
  FileChartColumn,
  KeyRound,
  LayoutDashboard,
  LogOut,
  Menu,
  Plus,
  Search,
  Settings2,
  ShieldCheck,
  Sparkles,
  Users,
  X,
} from '@lucide/vue'
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  categoryScores,
  filterSurveys,
  missingRequiredAnswers,
  navByRole,
  questions,
  surveys,
  unitMetrics,
  type DemoRole,
} from '@/prototype'

const route = useRoute()
const router = useRouter()
const role = ref<DemoRole>('respondent')
const drawerOpen = ref(false)
const surveyQuery = ref('')
const surveyStatus = ref('Semua')
const selectedUnit = ref<keyof typeof unitMetrics>('university')
const answers = ref<Record<string, number>>({})
const comment = ref('')
const autosaveStatus = ref('Belum ada perubahan.')
const validationAttempted = ref(false)
const submitted = ref(false)
const receipt = ref('')
const submitDialog = ref<HTMLDialogElement | null>(null)
const secretDialog = ref<HTMLDialogElement | null>(null)
const secretDraft = ref('')
const secretMessage = ref('Secret tersimpan sebagai referensi masked. Nilai tidak dapat ditampilkan kembali.')
const builderText = ref(questions[0]?.text ?? '')
const builderSavedText = ref(questions[0]?.text ?? '')
const builderStatus = ref('Draft fixture belum berubah.')
const aiState = ref<'ready' | 'running' | 'review'>('ready')
const findingStatus = ref('Berjalan')
const reportStatus = ref<'Belum dibuat' | 'Memproses' | 'Siap'>('Belum dibuat')
const reminderSent = ref(false)
let saveTimer: ReturnType<typeof setTimeout> | undefined
let aiTimer: ReturnType<typeof setTimeout> | undefined
let reportTimer: ReturnType<typeof setTimeout> | undefined

const screen = computed(() => String(route.name ?? 'login'))
const isLogin = computed(() => screen.value === 'login')
const filteredSurveys = computed(() => filterSurveys(surveyQuery.value, surveyStatus.value))
const missingAnswers = computed(() => missingRequiredAnswers(answers.value))
const leaderMetric = computed(() => unitMetrics[selectedUnit.value])
const navItems = computed(() => navByRole[role.value])

const iconsByRoute: Record<string, typeof LayoutDashboard> = {
  '/respondent': LayoutDashboard,
  '/surveys': ClipboardList,
  '/admin': LayoutDashboard,
  '/builder': ClipboardCheck,
  '/monitoring': Users,
  '/results': BarChart3,
  '/leadership': BarChart3,
  '/ai-analysis': Bot,
  '/follow-up': ShieldCheck,
  '/reports': FileChartColumn,
  '/ai-config': KeyRound,
}

function signIn() {
  const destination = role.value === 'respondent' ? '/respondent' : role.value === 'admin' ? '/admin' : '/leadership'
  void router.push(destination)
}

function focusMain() {
  document.getElementById('main-content')?.focus()
}

function switchRole() {
  signIn()
}

function navigate(to: string) {
  drawerOpen.value = false
  void router.push(to)
}

function markChanged() {
  autosaveStatus.value = 'Menyimpan perubahan lokal…'
  window.clearTimeout(saveTimer)
  saveTimer = window.setTimeout(() => {
    const time = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date())
    autosaveStatus.value = `Tersimpan di tab ini pukul ${time}.`
  }, 650)
}

function validateAndSubmit() {
  validationAttempted.value = true
  if (missingAnswers.value.length) {
    void nextTick(() => document.querySelector<HTMLElement>('.error-summary')?.focus())
    return
  }
  submitDialog.value?.showModal()
}

function confirmSubmit() {
  submitted.value = true
  receipt.value = `SIM-${new Date().getFullYear()}-00428`
  submitDialog.value?.close()
  void nextTick(() => document.querySelector<HTMLElement>('.success-panel')?.focus())
}

function saveBuilder() {
  builderSavedText.value = builderText.value.trim() || questions[0]?.text || ''
  builderStatus.value = 'Draft fixture tersimpan lokal. Review dan publikasi tidak dijalankan.'
}

function runAiSimulation() {
  aiState.value = 'running'
  window.clearTimeout(aiTimer)
  aiTimer = window.setTimeout(() => (aiState.value = 'review'), 900)
}

function openSecretDialog() {
  secretDraft.value = ''
  secretDialog.value?.showModal()
}

function saveSecretSimulation() {
  if (!secretDraft.value) return
  secretMessage.value = 'Secret mock diganti di memori tab. Nilai asli tidak disimpan atau ditampilkan.'
  secretDraft.value = ''
  secretDialog.value?.close()
}

function generateReport() {
  reportStatus.value = 'Memproses'
  window.clearTimeout(reportTimer)
  reportTimer = window.setTimeout(() => (reportStatus.value = 'Siap'), 850)
}

watch(
  screen,
  (current) => {
    if (['admin', 'builder', 'monitoring', 'results', 'ai-analysis', 'ai-config', 'follow-up'].includes(current)) role.value = 'admin'
    if (current === 'leadership') role.value = 'leader'
    if (['respondent', 'surveys', 'survey-detail', 'response-form'].includes(current)) role.value = 'respondent'
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  window.clearTimeout(saveTimer)
  window.clearTimeout(aiTimer)
  window.clearTimeout(reportTimer)
})
</script>

<template>
  <a class="skip-link" href="#main-content" @click.prevent="focusMain">Lewati ke konten utama</a>

  <main v-if="isLogin" id="main-content" class="login-page" tabindex="-1">
    <section class="login-panel" aria-labelledby="login-title">
      <div class="brand-lockup">
        <span class="brand-mark" aria-hidden="true">SM</span>
        <div><strong>SIMUTU</strong><span>Prototype Sistem Survei Mutu</span></div>
      </div>
      <p class="eyebrow">Phase 08 · Clickable prototype</p>
      <h1 id="login-title" tabindex="-1">Masuk ke ruang kerja demo</h1>
      <p class="lede">Pilih peran untuk meninjau alur. Tidak ada autentikasi, database, atau layanan eksternal yang digunakan.</p>
      <div class="prototype-notice" role="status"><ShieldCheck :size="20" aria-hidden="true" /> Seluruh data pada prototype adalah fixture.</div>
      <form class="login-form" @submit.prevent="signIn">
        <label for="demo-role">Akun demo</label>
        <select id="demo-role" v-model="role">
          <option value="respondent">Alya Putri — Responden</option>
          <option value="admin">Rina Dewi — Admin LPMPP</option>
          <option value="leader">Dr. Bima — Pimpinan</option>
        </select>
        <button class="button button-primary" type="submit">Masuk ke prototype</button>
      </form>
      <p class="fine-print">Dengan melanjutkan, Anda hanya membuka simulasi lokal tanpa data institusi nyata.</p>
    </section>
    <aside class="login-context" aria-label="Cakupan prototype">
      <p class="eyebrow">Pengalaman yang dapat diuji</p>
      <h2>Survei yang jelas, hasil yang dapat ditindaklanjuti.</h2>
      <ul class="feature-list">
        <li><Check :size="18" aria-hidden="true" /> Pengisian dan autosave simulasi</li>
        <li><Check :size="18" aria-hidden="true" /> Dashboard agregat dan scope unit</li>
        <li><Check :size="18" aria-hidden="true" /> AI draft dengan human review</li>
      </ul>
    </aside>
  </main>

  <div v-else class="app-shell">
    <header class="topbar">
      <button class="icon-button menu-button" type="button" aria-label="Buka navigasi" @click="drawerOpen = true"><Menu /></button>
      <button class="brand-button" type="button" @click="navigate(role === 'respondent' ? '/respondent' : role === 'admin' ? '/admin' : '/leadership')">
        <span class="brand-mark" aria-hidden="true">SM</span><span><strong>SIMUTU</strong><small>Prototype survei mutu</small></span>
      </button>
      <span class="mode-badge">Mode prototype</span>
      <div class="topbar-actions">
        <label class="sr-only" for="role-switch">Ganti peran demo</label>
        <select id="role-switch" v-model="role" @change="switchRole">
          <option value="respondent">Responden</option><option value="admin">Admin LPMPP</option><option value="leader">Pimpinan</option>
        </select>
        <button class="user-chip" type="button" @click="navigate('/login')"><span aria-hidden="true">AP</span><span>Keluar demo</span><LogOut :size="16" aria-hidden="true" /></button>
      </div>
    </header>

    <div v-if="drawerOpen" class="drawer-backdrop" @click.self="drawerOpen = false">
      <aside class="mobile-drawer" aria-label="Navigasi mobile">
        <div class="drawer-head"><strong>Menu</strong><button class="icon-button" type="button" aria-label="Tutup navigasi" @click="drawerOpen = false"><X /></button></div>
        <nav><button v-for="item in navItems" :key="item.to" type="button" :class="{ active: route.path === item.to }" @click="navigate(item.to)">{{ item.label }}</button></nav>
      </aside>
    </div>

    <aside class="sidebar">
      <nav aria-label="Navigasi utama">
        <p class="nav-label">Ruang kerja {{ role === 'respondent' ? 'responden' : role === 'admin' ? 'LPMPP' : 'pimpinan' }}</p>
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to" :aria-current="route.path === item.to ? 'page' : undefined">
          <component :is="iconsByRoute[item.to] ?? LayoutDashboard" :size="19" aria-hidden="true" />{{ item.label }}
        </RouterLink>
      </nav>
      <div class="sidebar-note"><ShieldCheck :size="20" aria-hidden="true" /><div><strong>Fixture only</strong><span>Tidak terhubung production.</span></div></div>
    </aside>

    <main id="main-content" class="page-content" tabindex="-1">
      <div class="global-notice" role="status"><strong>Mode prototype.</strong> Data dan tindakan di halaman ini hanya simulasi lokal.</div>

      <template v-if="screen === 'respondent'">
        <div class="page-heading"><div><p class="eyebrow">Beranda responden</p><h1 tabindex="-1">Selamat datang, Alya</h1><p class="lede">Selesaikan survei aktif sebelum periodenya berakhir.</p></div><button class="button button-secondary" type="button" @click="navigate('/surveys')">Lihat semua survei</button></div>
        <section class="kpi-grid" aria-label="Ringkasan survei"><article class="kpi-card"><span>Survei aktif</span><strong>2</strong><small>Dalam periode berjalan</small></article><article class="kpi-card"><span>Perlu dilanjutkan</span><strong>1</strong><small>Draft tersimpan</small></article><article class="kpi-card"><span>Selesai</span><strong>3</strong><small>Partisipasi tercatat</small></article></section>
        <section class="section-block"><div class="section-heading"><div><p class="eyebrow">Prioritas</p><h2>Perlu Anda isi</h2></div><span class="status warning">Berakhir 12 hari lagi</span></div><article class="survey-feature"><div><div class="badge-row"><span class="status active">Aktif</span><span class="status neutral">Rahasia</span></div><h3>{{ surveys[0]?.title }}</h3><p>{{ surveys[0]?.period }} · Estimasi {{ surveys[0]?.estimate }}</p><div class="progress-label"><span>Progres draft</span><strong>{{ surveys[0]?.progress }}%</strong></div><progress :value="surveys[0]?.progress" max="100">{{ surveys[0]?.progress }}%</progress></div><button class="button button-primary" type="button" @click="navigate('/responses/academic-service-2026')">Lanjutkan pengisian</button></article></section>
      </template>

      <template v-else-if="screen === 'surveys'">
        <div class="page-heading"><div><p class="eyebrow">Responden</p><h1 tabindex="-1">Survei Saya</h1><p class="lede">Daftar survei yang tersedia untuk akun demo.</p></div></div>
        <section class="toolbar" aria-label="Filter survei"><label class="search-field"><span>Cari survei</span><span class="input-with-icon"><Search :size="18" aria-hidden="true" /><input v-model="surveyQuery" type="search" placeholder="Judul atau periode" /></span></label><label><span>Status</span><select v-model="surveyStatus"><option>Semua</option><option>Aktif</option><option>Selesai</option><option>Akan datang</option></select></label><button v-if="surveyQuery || surveyStatus !== 'Semua'" class="button button-quiet" type="button" @click="surveyQuery = ''; surveyStatus = 'Semua'">Reset filter</button></section>
        <p class="result-count" aria-live="polite">{{ filteredSurveys.length }} survei ditemukan.</p>
        <section class="survey-list" aria-label="Hasil pencarian"><article v-for="survey in filteredSurveys" :key="survey.id" class="survey-row"><div><div class="badge-row"><span class="status" :class="survey.status === 'Aktif' ? 'active' : 'neutral'">{{ survey.status }}</span><span class="status neutral">{{ survey.identityMode }}</span></div><h2>{{ survey.title }}</h2><p>{{ survey.period }} · {{ survey.estimate }} · Progres {{ survey.progress }}%</p></div><button class="button button-secondary" type="button" @click="navigate(`/surveys/${survey.id}`)">Lihat detail</button></article><div v-if="!filteredSurveys.length" class="empty-state"><Search :size="28" aria-hidden="true" /><h2>Survei tidak ditemukan</h2><p>Ubah kata kunci atau reset filter.</p><button class="button button-primary" type="button" @click="surveyQuery = ''; surveyStatus = 'Semua'">Reset filter</button></div></section>
      </template>

      <template v-else-if="screen === 'survey-detail'">
        <button class="back-link" type="button" @click="navigate('/surveys')">← Kembali ke Survei Saya</button><div class="page-heading"><div><div class="badge-row"><span class="status active">Aktif</span><span class="status neutral">Rahasia</span></div><h1 tabindex="-1">Kepuasan Mahasiswa terhadap Layanan Akademik</h1><p class="lede">Mengukur pengalaman mahasiswa untuk perbaikan layanan akademik yang terencana.</p></div><button class="button button-primary" type="button" @click="navigate('/responses/academic-service-2026')">Mulai pengisian</button></div>
        <section class="detail-grid"><article class="panel"><h2>Informasi survei</h2><dl class="definition-list"><div><dt>Periode</dt><dd>Semester Genap 2025/2026</dd></div><div><dt>Estimasi</dt><dd>6–8 menit</dd></div><div><dt>Instrumen</dt><dd>3 bagian · 12 item · skala 1–4</dd></div><div><dt>Penyelenggara</dt><dd>LPMPP</dd></div></dl></article><article class="panel privacy-panel"><ShieldCheck :size="24" aria-hidden="true" /><div><h2>Respons bersifat rahasia</h2><p>Status partisipasi dipisahkan dari isi respons. Hasil hanya ditampilkan secara agregat setelah memenuhi ambang pelaporan.</p></div></article></section>
      </template>

      <template v-else-if="screen === 'response-form'">
        <div v-if="submitted" class="success-panel" tabindex="-1"><span class="success-icon"><Check aria-hidden="true" /></span><p class="eyebrow">Simulasi berhasil</p><h1 tabindex="-1">Respons simulasi telah dikirim</h1><p>Tidak ada data yang dikirim ke server. Nomor bukti fixture: <strong>{{ receipt }}</strong>.</p><button class="button button-primary" type="button" @click="navigate('/respondent')">Kembali ke beranda</button></div>
        <template v-else><div class="survey-form-head"><div><p class="eyebrow">Bagian 1 dari 3 · Layanan Akademik</p><h1 tabindex="-1">Kepuasan Layanan Akademik</h1></div><div class="autosave" aria-live="polite"><span class="autosave-dot" aria-hidden="true"></span>{{ autosaveStatus }}</div></div>
          <div class="form-layout"><aside class="stepper" aria-label="Progres bagian"><strong>Progres</strong><ol><li class="current"><span>1</span> Layanan akademik</li><li><span>2</span> Sarana pendukung</li><li><span>3</span> Saran dan tinjau</li></ol><p>Prototype memadatkan alur menjadi satu halaman.</p></aside><form class="survey-form" @submit.prevent="validateAndSubmit"><div v-if="validationAttempted && missingAnswers.length" class="error-summary" role="alert" tabindex="-1"><AlertTriangle :size="20" aria-hidden="true" /><div><strong>{{ missingAnswers.length }} jawaban wajib belum lengkap.</strong><p>Lengkapi item yang ditandai sebelum mengirim.</p></div></div><section class="panel"><h2>Penilaian kinerja layanan</h2><p>Pilih satu jawaban untuk setiap pernyataan: 1 Sangat tidak baik sampai 4 Sangat baik.</p><fieldset v-for="question in questions" :key="question.code" class="question-field" :class="{ invalid: validationAttempted && !answers[question.code] }"><legend><span>{{ question.code }} · {{ question.category }}</span>{{ question.text }} <em>Wajib</em></legend><p class="indicator">Indikator: {{ question.indicator }}</p><div class="scale-options"><label v-for="option in 4" :key="option"><input v-model="answers[question.code]" type="radio" :name="question.code" :value="option" @change="markChanged" /><span><strong>{{ option }}</strong>{{ option === 1 ? 'Sangat tidak baik' : option === 2 ? 'Tidak baik' : option === 3 ? 'Baik' : 'Sangat baik' }}</span></label></div><p v-if="validationAttempted && !answers[question.code]" class="field-error">Pilih satu jawaban untuk item {{ question.code }}.</p></fieldset><label class="textarea-field" for="survey-comment"><span>Saran perbaikan <em>Opsional</em></span><textarea id="survey-comment" v-model="comment" rows="4" placeholder="Tulis saran tanpa mencantumkan data pribadi" @input="markChanged"></textarea></label></section><div class="form-actions"><button class="button button-secondary" type="button" @click="navigate('/surveys/academic-service-2026')">Simpan dan keluar</button><button class="button button-primary" type="submit">Tinjau dan kirim</button></div></form></div></template>
      </template>

      <template v-else-if="screen === 'admin'">
        <div class="reference-banner"><Settings2 :size="20" aria-hidden="true" /><span><strong>Mock/reference Admin LPMPP.</strong> Operasi admin production direncanakan tetap menggunakan Filament.</span></div><div class="page-heading"><div><p class="eyebrow">Ruang kerja LPMPP</p><h1 tabindex="-1">Ikhtisar Admin LPMPP</h1><p class="lede">Pantau campaign, partisipasi, hasil agregat, dan tindak lanjut.</p></div><button class="button button-primary" type="button" @click="navigate('/builder')"><Plus :size="18" aria-hidden="true" /> Buka builder</button></div>
        <section class="kpi-grid four" aria-label="KPI admin"><article class="kpi-card"><span>Survei aktif</span><strong>6</strong><small>2 berakhir bulan ini</small></article><article class="kpi-card"><span>Response rate</span><strong>65,0%</strong><small>806 dari 1.240 target</small></article><article class="kpi-card"><span>Respons final</span><strong>806</strong><small>Snapshot 6 Agu 2026</small></article><article class="kpi-card warning-card"><span>Tindak lanjut terlambat</span><strong>4</strong><small>Perlu eskalasi</small></article></section>
        <section class="two-column"><article class="panel"><div class="section-heading"><div><p class="eyebrow">Perlu perhatian</p><h2>Agenda prioritas</h2></div></div><ul class="attention-list"><li><AlertTriangle aria-hidden="true" /><div><strong>Response rate Fakultas Ekonomi 48%</strong><span>Di bawah target awal 60%</span></div><button type="button" @click="navigate('/monitoring')">Pantau</button></li><li><ClipboardCheck aria-hidden="true" /><div><strong>2 instrumen menunggu review</strong><span>Tidak dipublikasikan oleh prototype</span></div><button type="button" @click="navigate('/builder')">Buka</button></li></ul></article><article class="panel"><p class="eyebrow">Status tindak lanjut</p><h2>18 aksi aktif</h2><div class="mini-bars"><div><span>Selesai</span><b style="--value: 67%">12</b></div><div><span>Berjalan</span><b style="--value: 22%">4</b></div><div><span>Terlambat</span><b style="--value: 11%">2</b></div></div><button class="text-link" type="button" @click="navigate('/follow-up')">Lihat seluruh tindak lanjut →</button></article></section>
      </template>

      <template v-else-if="screen === 'builder'">
        <div class="page-heading"><div><p class="eyebrow">Instrumen · Draft v1.3</p><h1 tabindex="-1">Builder Instrumen</h1><p class="lede">SERVPERF + IPA · belum direview · fixture lokal.</p></div><div class="button-row"><button class="button button-secondary" type="button">Pratinjau</button><button class="button button-primary" type="button" @click="saveBuilder">Simpan draft lokal</button></div></div><p class="inline-status" aria-live="polite">{{ builderStatus }}</p>
        <div class="builder-layout"><aside class="builder-outline"><strong>Struktur survei</strong><button class="active" type="button"><span>01</span> Layanan Akademik</button><button type="button"><span>02</span> Sarana Pendukung</button><button type="button"><Plus :size="16" aria-hidden="true" /> Tambah bagian</button></aside><section class="builder-canvas"><div class="section-heading"><div><p class="eyebrow">Bagian 1</p><h2>Layanan Akademik</h2></div><span class="status neutral">3 item</span></div><article class="question-card"><div><span>{{ questions[0]?.code }} · {{ questions[0]?.indicator }}</span><h3>{{ builderSavedText }}</h3><p>Skala 1–4 · Wajib · pasangan importance-performance</p></div><button class="button button-quiet" type="button">Edit</button></article><article v-for="question in questions.slice(1)" :key="question.code" class="question-card"><div><span>{{ question.code }} · {{ question.indicator }}</span><h3>{{ question.text }}</h3><p>Skala 1–4 · Wajib</p></div><button class="button button-quiet" type="button">Edit</button></article><button class="add-question" type="button"><Plus aria-hidden="true" /> Tambah pertanyaan</button></section><aside class="inspector"><p class="eyebrow">Inspector</p><h2>Edit item LA-01</h2><label for="builder-code">Kode<input id="builder-code" value="LA-01" disabled /></label><label for="builder-indicator">Indikator<input id="builder-indicator" value="Ketepatan informasi" /></label><label for="builder-text">Teks item<textarea id="builder-text" v-model="builderText" rows="6"></textarea></label><label class="check-field"><input type="checkbox" checked /> Item wajib</label><button class="button button-primary" type="button" @click="saveBuilder">Terapkan ke draft</button></aside></div>
      </template>

      <template v-else-if="screen === 'monitoring'">
        <div class="page-heading"><div><p class="eyebrow">Operasi campaign</p><h1 tabindex="-1">Monitoring Respons</h1><p class="lede">Agregat partisipasi saja—bukan isi respons.</p></div><button class="button button-secondary" type="button" @click="reminderSent = true">Simulasikan reminder</button></div><div v-if="reminderSent" class="inline-success" role="status"><Check :size="18" aria-hidden="true" /> Reminder fixture untuk 434 target belum merespons. Tidak ada email terkirim.</div><section class="kpi-grid four"><article class="kpi-card"><span>Target</span><strong>1.240</strong><small>Eligible invitations</small></article><article class="kpi-card"><span>Respons final</span><strong>806</strong><small>Tidak membuka isi</small></article><article class="kpi-card"><span>Draft</span><strong>127</strong><small>Belum final</small></article><article class="kpi-card"><span>Response rate</span><strong>65,0%</strong><small>Target awal 60%</small></article></section><section class="panel table-panel"><div class="section-heading"><div><p class="eyebrow">Per unit</p><h2>Partisipasi teragregasi</h2></div><label>Periode<select><option>Semester Genap 2025/2026</option></select></label></div><div class="responsive-table"><table><caption class="sr-only">Monitoring respons per unit</caption><thead><tr><th scope="col">Unit</th><th scope="col">Target</th><th scope="col">Final</th><th scope="col">Rate</th><th scope="col">Status</th></tr></thead><tbody><tr><th scope="row">Fakultas Teknik</th><td>420</td><td>299</td><td>71,2%</td><td><span class="status active">Di atas target</span></td></tr><tr><th scope="row">Fakultas Ekonomi</th><td>390</td><td>241</td><td>61,8%</td><td><span class="status active">Di atas target</span></td></tr><tr><th scope="row">Pascasarjana</th><td>28</td><td>8</td><td>Disembunyikan</td><td><span class="status warning">Privasi</span></td></tr></tbody></table></div></section>
      </template>

      <template v-else-if="screen === 'results'">
        <div class="page-heading"><div><p class="eyebrow">Released aggregate snapshot</p><h1 tabindex="-1">Hasil Survei</h1><p class="lede">Dirilis 6 Agustus 2026, 16.00 WIB · N=806 · SERVPERF + IPA.</p></div><button class="button button-secondary" type="button" @click="navigate('/reports')">Ke laporan</button></div><section class="kpi-grid four"><article class="kpi-card"><span>Indeks layanan</span><strong>82,4</strong><small>Skala normalisasi 0–100</small></article><article class="kpi-card"><span>Response rate</span><strong>65,0%</strong><small>806 / 1.240</small></article><article class="kpi-card"><span>Prioritas perbaikan</span><strong>3</strong><small>IPA kuadran prioritas</small></article><article class="kpi-card"><span>Tren</span><strong>+2,1</strong><small>Dibanding periode lalu</small></article></section><section class="two-column result-layout"><article class="panel"><p class="eyebrow">Skor kategori</p><h2>Kinerja layanan</h2><div class="bar-chart" role="img" aria-label="Skor kategori: Keandalan 84, Daya tanggap 77, Jaminan 86, Empati 81, Bukti fisik 83"><div v-for="item in categoryScores" :key="item.label"><span>{{ item.label }}</span><div><b :style="{ width: `${item.score}%` }"></b></div><strong>{{ item.score }}</strong></div></div></article><article class="panel"><p class="eyebrow">IPA</p><h2>Prioritas utama</h2><div class="priority-list"><div><span>01</span><p><strong>Kecepatan respons layanan</strong><small>Importance 3,72 · Performance 2,94</small></p></div><div><span>02</span><p><strong>Kejelasan status pengajuan</strong><small>Importance 3,64 · Performance 3,02</small></p></div><div><span>03</span><p><strong>Konsistensi informasi jadwal</strong><small>Importance 3,58 · Performance 3,08</small></p></div></div></article></section><div class="privacy-note"><ShieldCheck :size="20" aria-hidden="true" /><span><strong>Minimum reporting threshold diterapkan.</strong> Satu unit disembunyikan karena N di bawah ambang; nilai tidak diperlakukan sebagai nol.</span></div>
      </template>

      <template v-else-if="screen === 'leadership'">
        <div class="scope-banner"><ShieldCheck :size="20" aria-hidden="true" /><span>Hanya agregat yang sudah dirilis dan berada dalam scope organisasi Anda.</span></div><div class="page-heading"><div><p class="eyebrow">Pimpinan</p><h1 tabindex="-1">Dashboard Pimpinan</h1><p class="lede">Ringkasan mutu layanan dan progres perbaikan.</p></div><label class="unit-filter">Unit dalam scope<select v-model="selectedUnit"><option value="university">Seluruh unit dalam scope</option><option value="engineering">Fakultas Teknik</option><option value="economics">Fakultas Ekonomi</option></select></label></div><p class="result-count" aria-live="polite">Menampilkan {{ leaderMetric.label }}.</p><section class="kpi-grid four"><article class="kpi-card"><span>Indeks layanan</span><strong>{{ leaderMetric.score }}</strong><small>Released snapshot</small></article><article class="kpi-card"><span>Response rate</span><strong>{{ leaderMetric.rate }}</strong><small>Partisipasi eligible</small></article><article class="kpi-card"><span>Prioritas</span><strong>{{ leaderMetric.priority }}</strong><small>Perlu keputusan</small></article><article class="kpi-card"><span>Aksi selesai</span><strong>{{ leaderMetric.actions }}</strong><small>Sudah diverifikasi</small></article></section><section class="two-column"><article class="panel"><p class="eyebrow">Fokus perbaikan</p><h2>Tiga isu prioritas</h2><ol class="rank-list"><li><span>01</span><div><strong>Waktu tanggap layanan</strong><small>Gap −0,78 · 2 aksi berjalan</small></div></li><li><span>02</span><div><strong>Status pengajuan akademik</strong><small>Gap −0,62 · 1 aksi terlambat</small></div></li><li><span>03</span><div><strong>Konsistensi jadwal</strong><small>Gap −0,50 · 1 aksi selesai</small></div></li></ol></article><article class="panel"><p class="eyebrow">Closing the loop</p><h2>Komitmen perbaikan</h2><div class="donut-summary"><div><strong>67%</strong><span>Terverifikasi</span></div><ul><li><i class="dot done"></i>Selesai 12</li><li><i class="dot active-dot"></i>Berjalan 4</li><li><i class="dot late"></i>Terlambat 2</li></ul></div><button class="text-link" type="button" @click="navigate('/reports')">Buka laporan dirilis →</button></article></section>
      </template>

      <template v-else-if="screen === 'ai-analysis'">
        <div class="ai-banner"><Sparkles :size="20" aria-hidden="true" /><strong>SIMULASI AI · POST-MVP · DRAFT · WAJIB REVIEW MANUSIA</strong></div><div class="page-heading"><div><p class="eyebrow">Eksperimen terkontrol</p><h1 tabindex="-1">Analisis AI</h1><p class="lede">Menguji presentasi tema dari fixture agregat dan komentar yang telah disamarkan.</p></div><button class="button button-primary" type="button" :disabled="aiState === 'running'" @click="runAiSimulation">{{ aiState === 'ready' ? 'Jalankan simulasi' : aiState === 'running' ? 'Menganalisis fixture…' : 'Jalankan ulang' }}</button></div><section class="guardrail-grid"><article><ShieldCheck aria-hidden="true" /><span><strong>Provider nyata OFF</strong>Tidak ada network request</span></article><article><Check aria-hidden="true" /><span><strong>Redaction ON</strong>Fixture tanpa identitas</span></article><article><FileChartColumn aria-hidden="true" /><span><strong>Human review</strong>Wajib sebelum rilis</span></article></section><div v-if="aiState === 'ready'" class="empty-state"><Bot :size="32" aria-hidden="true" /><h2>Belum ada run simulasi</h2><p>Jalankan simulasi untuk melihat output fixture yang tetap membutuhkan review.</p></div><div v-else-if="aiState === 'running'" class="skeleton-stack" role="status" aria-live="polite"><p>Mengolah fixture lokal…</p><span></span><span></span><span></span></div><section v-else class="ai-results"><div class="section-heading"><div><p class="eyebrow">Run AI-MOCK-014</p><h2>Draft membutuhkan review</h2></div><span class="status warning">Needs review</span></div><div class="theme-grid"><article><span class="status neutral">Tema 1 · bukti 18</span><h3>Kejelasan status layanan</h3><p>Responden fixture menginginkan pelacakan status pengajuan yang konsisten.</p><small>Confidence label: sedang · bukan probabilitas kebenaran</small></article><article><span class="status neutral">Tema 2 · bukti 11</span><h3>Waktu tanggap</h3><p>Waktu penyelesaian dirasakan berbeda antar kanal layanan.</p><small>Keterbatasan: sample komentar tidak mewakili seluruh populasi</small></article></div><div class="review-box"><h3>Checklist reviewer manusia</h3><label><input type="checkbox" /> Klaim sesuai bukti agregat</label><label><input type="checkbox" /> Tidak mengungkap identitas</label><label><input type="checkbox" /> Keterbatasan dinyatakan</label><div class="button-row"><button class="button button-secondary" type="button">Tolak draft</button><button class="button button-primary" type="button">Tandai direview (mock)</button></div></div></section>
      </template>

      <template v-else-if="screen === 'ai-config'">
        <div class="ai-banner neutral-banner"><KeyRound :size="20" aria-hidden="true" /><strong>PROTOTYPE · PROVIDER NYATA TIDAK TERHUBUNG</strong></div><div class="page-heading"><div><p class="eyebrow">Pengaturan terbatas</p><h1 tabindex="-1">Konfigurasi AI</h1><p class="lede">Referensi UI untuk secret write-only dan provider allowlist.</p></div><span class="status neutral">Global OFF</span></div><section class="settings-grid"><article class="panel"><h2>Provider dan model</h2><dl class="definition-list"><div><dt>Provider terdaftar</dt><dd>Provider Demo (allowlisted)</dd></div><div><dt>Model</dt><dd>model-survey-demo</dd></div><div><dt>Custom Base URL</dt><dd>Tidak tersedia</dd></div><div><dt>Batas biaya</dt><dd>Rp0 — simulasi</dd></div></dl></article><article class="panel"><h2>Credential provider</h2><label for="masked-secret">Secret tersimpan</label><input id="masked-secret" value="••••••••••••7A9C" readonly /><p>{{ secretMessage }}</p><button class="button button-secondary" type="button" @click="openSecretDialog">Ganti secret mock</button></article></section><section class="panel"><p class="eyebrow">Audit preview</p><h2>Aktivitas konfigurasi</h2><div class="audit-row"><span>Rina Dewi</span><strong>CONFIG_REFERENCE_VIEWED</strong><span>7 Agustus 2026, 09.10 WIB</span><span class="status active">Tanpa secret</span></div></section>
      </template>

      <template v-else-if="screen === 'follow-up'">
        <div class="page-heading"><div><p class="eyebrow">PPEPP · Closing the loop</p><h1 tabindex="-1">Temuan dan Tindak Lanjut</h1><p class="lede">Hubungkan temuan agregat ke aksi, evidensi, dan verifikasi.</p></div><button class="button button-primary" type="button"><Plus :size="18" aria-hidden="true" /> Temuan mock</button></div><section class="kpi-grid four"><article class="kpi-card"><span>Terbuka</span><strong>6</strong><small>Belum memiliki aksi</small></article><article class="kpi-card"><span>Berjalan</span><strong>9</strong><small>Dalam target</small></article><article class="kpi-card"><span>Perlu verifikasi</span><strong>3</strong><small>Evidensi diajukan</small></article><article class="kpi-card warning-card"><span>Terlambat</span><strong>4</strong><small>Perlu eskalasi</small></article></section><section class="two-column follow-layout"><article class="panel table-panel"><h2>Daftar temuan</h2><div class="finding-list"><button class="active" type="button"><span>FND-2026-018</span><strong>Waktu tanggap layanan akademik</strong><small>Owner: BAAK · 30 Sep 2026</small></button><button type="button"><span>FND-2026-017</span><strong>Kejelasan status pengajuan</strong><small>Owner: UPT TIK · 15 Sep 2026</small></button><button type="button"><span>FND-2026-011</span><strong>Konsistensi informasi jadwal</strong><small>Owner: Program Studi · 31 Agu 2026</small></button></div></article><article class="panel"><div class="section-heading"><div><p class="eyebrow">FND-2026-018</p><h2>Waktu tanggap layanan akademik</h2></div><span class="status warning">{{ findingStatus }}</span></div><p>Sumber: snapshot agregat SRV-2026-ACADEMIC · gap IPA −0,78. Tidak ada raw response.</p><dl class="definition-list"><div><dt>Owner</dt><dd>BAAK</dd></div><div><dt>Target</dt><dd>SLA awal dan dashboard status tersedia</dd></div><div><dt>Deadline</dt><dd>30 September 2026</dd></div></dl><label for="finding-status">Simulasikan status<select id="finding-status" v-model="findingStatus"><option>Berjalan</option><option>Perlu verifikasi</option><option>Terverifikasi</option></select></label><div class="timeline"><div><span></span><p><strong>Aksi dibuat</strong><small>7 Agustus 2026 · Admin LPMPP</small></p></div><div><span></span><p><strong>Evidensi mock ditambahkan</strong><small>SOP-LAYANAN-v2.pdf · metadata fixture</small></p></div></div></article></section>
      </template>

      <template v-else-if="screen === 'reports'">
        <div class="page-heading"><div><p class="eyebrow">Released snapshots only</p><h1 tabindex="-1">Laporan</h1><p class="lede">Simulasikan pembuatan laporan dengan scope dan threshold yang terlihat.</p></div></div><section class="report-builder panel"><div><label for="report-template">Template laporan<select id="report-template"><option>Laporan Kepuasan Layanan Akademik</option></select></label><label for="report-format">Format<select id="report-format"><option>PDF</option><option>XLSX</option></select></label><label for="report-scope">Scope<select id="report-scope"><option>Seluruh unit dalam scope</option><option>Fakultas Teknik</option></select></label></div><div class="privacy-note"><ShieldCheck :size="20" aria-hidden="true" /><span>Minimum reporting threshold dan organizational scope diterapkan ulang pada implementasi production.</span></div><button class="button button-primary" type="button" :disabled="reportStatus === 'Memproses'" @click="generateReport">{{ reportStatus === 'Memproses' ? 'Membuat fixture…' : 'Simulasikan pembuatan' }}</button></section><section class="panel table-panel"><div class="section-heading"><div><p class="eyebrow">Job fixture</p><h2>Riwayat laporan</h2></div><span class="status" :class="reportStatus === 'Siap' ? 'active' : 'neutral'">{{ reportStatus }}</span></div><div class="responsive-table"><table><caption class="sr-only">Riwayat pembuatan laporan</caption><thead><tr><th scope="col">Report ID</th><th scope="col">Snapshot</th><th scope="col">Scope</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead><tbody><tr><th scope="row">RPT-MOCK-028</th><td>6 Agu 2026, 16.00</td><td>Dalam scope pengguna</td><td>{{ reportStatus }}</td><td><button v-if="reportStatus === 'Siap'" class="text-link" type="button">Lihat fixture</button><span v-else>—</span></td></tr><tr><th scope="row">RPT-MOCK-021</th><td>1 Feb 2026, 09.00</td><td>Fakultas Teknik</td><td>Siap</td><td><button class="text-link" type="button">Lihat fixture</button></td></tr></tbody></table></div><p class="fine-print">Prototype tidak membuat atau mengunduh file nyata.</p></section>
      </template>
    </main>
  </div>

  <dialog ref="submitDialog" class="dialog" aria-labelledby="submit-title"><form method="dialog"><button class="dialog-close" type="submit" aria-label="Tutup konfirmasi"><X /></button><span class="dialog-icon"><ClipboardCheck aria-hidden="true" /></span><h2 id="submit-title">Kirim respons final?</h2><p>Setelah dikirim, respons tidak dapat diubah pada alur ini. Ini hanya simulasi dan tidak mengirim data ke server.</p><div class="dialog-actions"><button class="button button-secondary" type="submit">Batal</button><button class="button button-primary" type="button" @click="confirmSubmit">Ya, kirim simulasi</button></div></form></dialog>
  <dialog ref="secretDialog" class="dialog" aria-labelledby="secret-title"><form method="dialog" @submit.prevent="saveSecretSimulation"><button class="dialog-close" type="submit" aria-label="Tutup dialog secret"><X /></button><span class="dialog-icon"><KeyRound aria-hidden="true" /></span><h2 id="secret-title">Ganti secret mock</h2><p>Nilai hanya hidup di memori tab dan tidak dikirim ke provider atau server.</p><label for="secret-value">Secret baru<input id="secret-value" v-model="secretDraft" type="password" required autocomplete="new-password" /></label><div class="dialog-actions"><button class="button button-secondary" type="button" @click="secretDialog?.close()">Batal</button><button class="button button-primary" type="submit">Simpan simulasi</button></div></form></dialog>
</template>
