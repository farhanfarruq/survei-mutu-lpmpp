<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue'
import { reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormField from '@/components/ui/FormField.vue'
import { destinationAfterLogin } from '@/navigation'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const form = reactive({ identity_number: '', password: '', remember: false })

async function submit() {
  try {
    await auth.login(form.identity_number, form.password, form.remember)
    const destination = destinationAfterLogin(auth.user, route.query.redirect)

    if (destination.external) window.location.assign(destination.to)
    else await router.replace(destination.to)
  } catch {
    // The store exposes a normalized, non-sensitive message next to the form.
  }
}
</script>

<template>
  <main id="main-content" class="login-page foundation-login">
    <section class="login-panel" aria-labelledby="login-title">
      <div class="brand-lockup">
        <span class="brand-mark" aria-hidden="true">SM</span>
        <div><strong>SIMUTU</strong><span>Sistem Survei Mutu LPMPP</span></div>
      </div>
      <p class="eyebrow">Akses survei</p>
      <h1 id="login-title" tabindex="-1">Masuk ke sistem</h1>
      <p class="lede">Masukkan NIM, nomor dosen, atau ID akun yang terdaftar.</p>
      <BaseAlert v-if="auth.error" tone="error" title="Login gagal"
        ><p>{{ auth.error.message }}</p>
        <small v-if="auth.error.requestId">Request ID: {{ auth.error.requestId }}</small></BaseAlert
      >
      <form class="login-form" @submit.prevent="submit">
        <FormField
          id="identity-number"
          label="NIM / nomor dosen / ID akun"
          :error="auth.error?.fields.identity_number?.[0]"
        >
          <template #default="{ describedBy }"
            ><input
              id="identity-number"
              v-model="form.identity_number"
              type="text"
              autocomplete="username"
              autocapitalize="characters"
              required
              :aria-describedby="describedBy"
          /></template>
        </FormField>
        <FormField id="password" label="Kata sandi" :error="auth.error?.fields.password?.[0]">
          <template #default="{ describedBy }"
            ><input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              :aria-describedby="describedBy"
          /></template>
        </FormField>
        <label class="remember-field"
          ><input v-model="form.remember" type="checkbox" /> Ingat sesi pada perangkat ini</label
        >
        <BaseButton type="submit" :loading="auth.loading">Masuk</BaseButton>
      </form>
      <p class="fine-print">
        Belum memiliki akun? <RouterLink to="/register">Buat akun responden</RouterLink>
      </p>
    </section>
    <aside class="login-context" aria-label="Informasi login">
      <p class="eyebrow">Login lebih mudah</p>
      <h2>Tidak perlu mengingat alamat email.</h2>
      <ul class="feature-list">
        <li><ShieldCheck :size="18" aria-hidden="true" /> Gunakan nomor identitas akun</li>
        <li><ShieldCheck :size="18" aria-hidden="true" /> Survei sesuai program studi</li>
        <li><ShieldCheck :size="18" aria-hidden="true" /> Satu akun untuk satu pengguna</li>
      </ul>
    </aside>
  </main>
</template>
