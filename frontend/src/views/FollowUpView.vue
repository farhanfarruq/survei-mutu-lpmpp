<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import { phase13Api, type Assignee, type Finding, type FollowUpAction, type FollowUpDashboard } from '@/services/phase13'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const findings = ref<Finding[]>([])
const dashboard = ref<FollowUpDashboard | null>(null)
const action = ref<FollowUpAction | null>(null)
const assignees = ref<Assignee[]>([])
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const status = ref('')
const filter = ref('')
const tomorrow = new Date(Date.now() + 86_400_000).toISOString().slice(0, 10)
const findingForm = reactive({ source_type: 'manual', owner_unit_id: auth.user?.organizational_units[0]?.id ?? '', title: '', description: '', source_evidence: '', severity: 'medium', due_on: tomorrow })
const actionForm = reactive({ findingId: '', pic_user_id: '', verifier_user_id: '', title: '', root_cause: '', plan: '', expected_output: '', resource_needs: '', due_on: tomorrow })
const workForm = reactive({ root_cause: '', plan: '', expected_output: '', resource_needs: '', progress: 0, rejection_reason: '' })
const evidenceForm = reactive({ title: '', description: '', reference_url: '' })
const verificationForm = reactive({ decision: 'verified', reason: '', evidence_review: '' })
const isDetail = computed(() => typeof route.params.id === 'string')

async function load() {
  loading.value = true; error.value = ''
  try {
    if (isDetail.value) {
      action.value = await phase13Api.action(String(route.params.id))
      Object.assign(workForm, { root_cause: action.value.root_cause, plan: action.value.plan, expected_output: action.value.expected_output, resource_needs: action.value.resource_needs ?? '', progress: action.value.progress })
    } else {
      const tasks: Promise<unknown>[] = [phase13Api.findings(filter.value).then((value) => { findings.value = value })]
      if (auth.can('follow-up.dashboard.read')) tasks.push(phase13Api.followUpDashboard().then((value) => { dashboard.value = value }))
      await Promise.all(tasks)
    }
  } catch (caught) { error.value = normalizeApiError(caught).message }
  finally { loading.value = false }
}

async function createFinding() { await run(async () => { await phase13Api.createFinding(findingForm); status.value = 'Finding manual dibuat dan diaudit.'; await load() }) }
async function loadAssignees() { const finding = findings.value.find((item) => item.id === actionForm.findingId); assignees.value = finding ? await phase13Api.assignees(finding.owner_unit_id) : [] }
async function createAction() { await run(async () => { await phase13Api.createAction(actionForm.findingId, { ...actionForm, pic_user_id: Number(actionForm.pic_user_id), verifier_user_id: Number(actionForm.verifier_user_id), findingId: undefined }); status.value = 'Action ditugaskan dengan PIC dan verifier terpisah.'; await load() }) }
async function updateAction(state = 'in_progress') { if (!action.value) return; await run(async () => { action.value = await phase13Api.updateAction(action.value!, { ...workForm, state }); status.value = 'Action diperbarui.' }) }
async function addEvidence() { if (!action.value) return; await run(async () => { await phase13Api.addEvidence(action.value!.id, evidenceForm); status.value = 'Evidence dicatat dengan checksum dan versi.'; await load() }) }
async function submit() { if (!action.value) return; await run(async () => { action.value = await phase13Api.submitAction(action.value!); status.value = 'Action diajukan untuk verifikasi.' }) }
async function verify() { if (!action.value) return; await run(async () => { action.value = await phase13Api.verifyAction(action.value!, verificationForm); status.value = 'Keputusan verifikasi dicatat.' }) }
async function run(callback: () => Promise<void>) { busy.value = true; error.value = ''; status.value = ''; try { await callback() } catch (caught) { error.value = normalizeApiError(caught).message } finally { busy.value = false } }

onMounted(load)
</script>

<template>
  <section aria-labelledby="follow-title">
    <div class="page-heading"><div><p class="eyebrow">Perbaikan mutu</p><h1 id="follow-title" tabindex="-1">Tindak Lanjut</h1><p v-if="!isDetail">Finding, assignment, tenggat, evidence, dan verifikasi dalam scope organisasi Anda.</p><p v-else>Detail action dan revision loop.</p></div></div>
    <p v-if="loading" role="status">Memuat tindak lanjut…</p>
    <BaseAlert v-if="error" tone="error" title="Tindak lanjut gagal">{{ error }} <button class="button button-secondary" @click="load">Coba lagi</button></BaseAlert>
    <p v-if="status" class="inline-success" role="status">{{ status }}</p>

    <template v-if="!loading && !isDetail">
      <section v-if="dashboard" class="kpi-grid" aria-label="Status tindak lanjut"><article class="kpi-card"><span>Total action</span><strong>{{ dashboard.total }}</strong></article><article class="kpi-card"><span>Terlambat</span><strong>{{ dashboard.overdue }}</strong></article><article class="kpi-card"><span>Menunggu verifikasi</span><strong>{{ dashboard.pending_verification }}</strong></article><article class="kpi-card"><span>Perlu revisi</span><strong>{{ dashboard.revision }}</strong></article></section>
      <div class="toolbar"><label>Status<select v-model="filter" @change="load"><option value="">Semua</option><option value="open">Open</option><option value="in_progress">In progress</option><option value="verified">Verified</option></select></label></div>
      <div v-if="findings.length === 0" class="empty-state"><h2>Belum ada finding</h2><p>Finding dapat dibuat manual atau dari indikator rendah yang tidak suppressed.</p></div>
      <div v-else class="responsive-table table-panel"><table><caption class="sr-only">Daftar finding dan action</caption><thead><tr><th>Kode</th><th>Finding</th><th>Unit</th><th>Status</th><th>Tenggat</th><th>Action</th></tr></thead><tbody><tr v-for="item in findings" :key="item.id"><td>{{ item.code }}</td><td><strong>{{ item.title }}</strong><br><small>{{ item.severity }} · {{ item.source_type }}</small></td><td>{{ item.unit }}</td><td>{{ item.state }}</td><td>{{ item.due_on }}</td><td><RouterLink v-for="row in item.actions" :key="row.id" class="table-link" :to="`/app/follow-ups/actions/${row.id}`">{{ row.title }}</RouterLink><span v-if="!item.actions.length">Belum ada</span></td></tr></tbody></table></div>

      <div class="phase13-grid">
        <form v-if="auth.can('finding.create')" class="panel phase13-form" @submit.prevent="createFinding"><h2>Buat finding manual</h2><label>Unit<select v-model="findingForm.owner_unit_id" required><option v-for="unit in auth.user?.organizational_units" :key="unit.id" :value="unit.id">{{ unit.name }}</option></select></label><label>Judul<input v-model="findingForm.title" required></label><label>Deskripsi<textarea v-model="findingForm.description" required></textarea></label><label>Dasar/evidence sumber<textarea v-model="findingForm.source_evidence" required></textarea></label><label>Severity<select v-model="findingForm.severity"><option>low</option><option>medium</option><option>high</option><option>critical</option></select></label><label>Tenggat<input v-model="findingForm.due_on" type="date" required></label><button class="button button-primary" :disabled="busy">Buat finding</button></form>
        <form v-if="auth.can('action.create')" class="panel phase13-form" @submit.prevent="createAction"><h2>Assign action</h2><label>Finding<select v-model="actionForm.findingId" required @change="loadAssignees"><option value="">Pilih</option><option v-for="item in findings.filter((row) => row.state !== 'verified')" :key="item.id" :value="item.id">{{ item.code }} · {{ item.title }}</option></select></label><div class="phase13-fields"><label>PIC<select v-model="actionForm.pic_user_id" required><option value="">Pilih</option><option v-for="user in assignees.filter((row) => row.can_update)" :key="user.id" :value="user.id">{{ user.name }}</option></select></label><label>Verifier<select v-model="actionForm.verifier_user_id" required><option value="">Pilih</option><option v-for="user in assignees.filter((row) => row.can_verify)" :key="user.id" :value="user.id">{{ user.name }}</option></select></label></div><label>Judul action<input v-model="actionForm.title" required></label><label>Root cause<textarea v-model="actionForm.root_cause" required></textarea></label><label>Rencana<textarea v-model="actionForm.plan" required></textarea></label><label>Output yang diharapkan<textarea v-model="actionForm.expected_output" required></textarea></label><label>Kebutuhan sumber daya<textarea v-model="actionForm.resource_needs"></textarea></label><label>Tenggat<input v-model="actionForm.due_on" type="date" required></label><button class="button button-primary" :disabled="busy">Tetapkan</button></form>
      </div>
    </template>

    <template v-if="!loading && isDetail && action">
      <div class="panel"><RouterLink to="/app/follow-up">← Kembali</RouterLink><h2>{{ action.title }}</h2><dl class="definition-list"><div><dt>Status</dt><dd>{{ action.state }}</dd></div><div><dt>PIC</dt><dd>{{ action.pic?.name }}</dd></div><div><dt>Verifier</dt><dd>{{ action.verifier?.name }}</dd></div><div><dt>Progress</dt><dd>{{ action.progress }}%</dd></div><div><dt>Tenggat</dt><dd>{{ action.due_on }}</dd></div><div v-if="action.assignment_note"><dt>Catatan</dt><dd>{{ action.assignment_note }}</dd></div></dl></div>
      <div class="phase13-grid">
        <form v-if="auth.can('action.update')" class="panel phase13-form" @submit.prevent="updateAction()"><h2>Pekerjaan PIC</h2><label>Root cause<textarea v-model="workForm.root_cause" required></textarea></label><label>Rencana<textarea v-model="workForm.plan" required></textarea></label><label>Output<textarea v-model="workForm.expected_output" required></textarea></label><label>Progress<input v-model.number="workForm.progress" type="range" min="0" max="100"> {{ workForm.progress }}%</label><label v-if="action.state === 'assigned'">Alasan bila menolak<textarea v-model="workForm.rejection_reason"></textarea></label><div class="phase13-actions"><button v-if="action.state === 'assigned'" type="button" class="button button-primary" @click="updateAction('accepted')">Terima</button><button v-if="action.state === 'assigned'" type="button" class="button button-danger" @click="updateAction('rejected')">Tolak</button><button v-else class="button button-primary" :disabled="busy">Simpan progress</button><button v-if="workForm.progress === 100 && ['accepted', 'in_progress'].includes(action.state)" type="button" class="button button-secondary" @click="submit">Ajukan verifikasi</button></div></form>
        <form v-if="auth.can('action.update') && ['accepted', 'in_progress', 'needs_revision'].includes(action.state)" class="panel phase13-form" @submit.prevent="addEvidence"><h2>Tambah evidence</h2><label>Judul<input v-model="evidenceForm.title" required></label><label>Deskripsi<textarea v-model="evidenceForm.description" required></textarea></label><label>URL HTTPS<input v-model="evidenceForm.reference_url" type="url"></label><button class="button button-secondary" :disabled="busy">Catat evidence</button></form>
        <form v-if="auth.can('action.verify') && action.state === 'pending_verification'" class="panel phase13-form" @submit.prevent="verify"><h2>Verifikasi independen</h2><label>Keputusan<select v-model="verificationForm.decision"><option value="verified">Verified</option><option value="needs_revision">Perlu revisi</option><option value="rejected">Ditolak</option></select></label><label>Alasan<textarea v-model="verificationForm.reason" required></textarea></label><label>Review evidence<textarea v-model="verificationForm.evidence_review" required></textarea></label><button class="button button-primary" :disabled="busy">Simpan keputusan</button></form>
      </div>
      <div class="phase13-grid"><section class="panel"><h2>Riwayat evidence</h2><pre class="phase13-json">{{ JSON.stringify(action.evidence, null, 2) }}</pre></section><section class="panel"><h2>Riwayat verifikasi</h2><pre class="phase13-json">{{ JSON.stringify(action.verifications, null, 2) }}</pre></section></div>
    </template>
  </section>
</template>
