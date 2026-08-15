<script setup lang="ts">
import { UserRoundPlus } from '@lucide/vue'
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormField from '@/components/ui/FormField.vue'
import { api, normalizeApiError } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

type Program = { id: string; code: string; name: string; faculty_name: string | null }

const auth = useAuthStore()
const router = useRouter()
const programs = ref<Program[]>([])
const loadingPrograms = ref(true)
const optionsError = ref('')
const form = reactive({
  name: '',
  account_type: 'student' as 'student' | 'lecturer',
  identity_number: '',
  organizational_unit_id: '',
  password: '',
  password_confirmation: '',
})

onMounted(async () => {
  try {
    programs.value = (
      await api.get<{ data: { programs: Program[] } }>('/api/v1/auth/registration-options')
    ).data.data.programs
  } catch (caught) {
    optionsError.value = normalizeApiError(caught).message
  } finally {
    loadingPrograms.value = false
  }
})

async function submit() {
  try {
    await auth.register({ ...form, identity_number: form.identity_number.trim().toUpperCase() })
    await router.replace('/app/surveys')
  } catch {
    // The store exposes validation errors next to their fields.
  }
}
</script>

<template>
  <main id="main-content" class="login-page foundation-login">
    <section class="login-panel" aria-labelledby="register-title">
      <div class="brand-lockup">
        <img class="brand-logo" src="/itda-logo.webp" alt="Logo ITDA" />
        <div><strong>SIMUTU</strong><span>Sistem Survei Mutu LPMPP</span></div>
      </div>
      <p class="eyebrow">Akun responden</p>
      <h1 id="register-title" tabindex="-1">Buat akun</h1>
      <p class="lede">
        Isi identitas akademik dan program studi Anda. Akun ini hanya dapat digunakan untuk mengisi
        survei.
      </p>
      <BaseAlert v-if="optionsError" tone="error" title="Program studi tidak dapat dimuat"
        ><p>{{ optionsError }}</p></BaseAlert
      >
      <BaseAlert v-if="auth.error" tone="error" title="Akun belum dapat dibuat"
        ><p>{{ auth.error.message }}</p></BaseAlert
      >
      <form class="login-form" @submit.prevent="submit">
        <FormField id="name" label="Nama lengkap" :error="auth.error?.fields.name?.[0]"
          ><template #default="{ describedBy }"
            ><input
              id="name"
              v-model="form.name"
              type="text"
              autocomplete="name"
              required
              :aria-describedby="describedBy" /></template
        ></FormField>
        <FormField
          id="account-type"
          label="Saya adalah"
          :error="auth.error?.fields.account_type?.[0]"
          ><template #default="{ describedBy }"
            ><select
              id="account-type"
              v-model="form.account_type"
              required
              :aria-describedby="describedBy"
            >
              <option value="student">Mahasiswa</option>
              <option value="lecturer">Dosen</option>
            </select></template
          ></FormField
        >
        <FormField
          id="identity-number"
          label="NIM / nomor dosen"
          :error="auth.error?.fields.identity_number?.[0]"
          ><template #default="{ describedBy }"
            ><input
              id="identity-number"
              v-model="form.identity_number"
              type="text"
              autocomplete="username"
              autocapitalize="characters"
              required
              :aria-describedby="describedBy" /></template
        ></FormField>
        <FormField
          id="program"
          label="Program studi"
          :error="auth.error?.fields.organizational_unit_id?.[0]"
          ><template #default="{ describedBy }"
            ><select
              id="program"
              v-model="form.organizational_unit_id"
              :disabled="loadingPrograms"
              required
              :aria-describedby="describedBy"
            >
              <option value="">
                {{ loadingPrograms ? 'Memuat program studi…' : 'Pilih program studi' }}
              </option>
              <option v-for="program in programs" :key="program.id" :value="program.id">
                {{ program.name }}{{ program.faculty_name ? ` — ${program.faculty_name}` : '' }}
              </option>
            </select></template
          ></FormField
        >
        <FormField
          id="password"
          label="Kata sandi"
          hint="Minimal 12 karakter."
          :error="auth.error?.fields.password?.[0]"
          ><template #default="{ describedBy }"
            ><input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="new-password"
              minlength="12"
              required
              :aria-describedby="describedBy" /></template
        ></FormField>
        <FormField id="password-confirmation" label="Ulangi kata sandi"
          ><template #default="{ describedBy }"
            ><input
              id="password-confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              minlength="12"
              required
              :aria-describedby="describedBy" /></template
        ></FormField>
        <BaseButton type="submit" :loading="auth.loading" :disabled="loadingPrograms"
          >Buat akun</BaseButton
        >
      </form>
      <p class="fine-print">
        Sudah memiliki akun? <RouterLink to="/login">Kembali ke login</RouterLink>
      </p>
    </section>
    <aside class="login-context" aria-label="Informasi akun">
      <p class="eyebrow">Sederhana dan aman</p>
      <h2>Satu nomor identitas untuk satu akun.</h2>
      <ul class="feature-list">
        <li><UserRoundPlus :size="18" aria-hidden="true" /> Tidak perlu mengingat email</li>
        <li><UserRoundPlus :size="18" aria-hidden="true" /> Survei mengikuti program studi</li>
        <li><UserRoundPlus :size="18" aria-hidden="true" /> Akun baru selalu menjadi responden</li>
      </ul>
    </aside>
  </main>
</template>
