<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue'
import { reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormField from '@/components/ui/FormField.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const form = reactive({ email: '', password: '', remember: false })

async function submit() {
  try {
    await auth.login(form.email, form.password, form.remember)
    const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/app') ? route.query.redirect : '/app'
    await router.replace(redirect)
  } catch {
    // The store exposes a normalized, non-sensitive message next to the form.
  }
}
</script>

<template>
  <main id="main-content" class="login-page foundation-login">
    <section class="login-panel" aria-labelledby="login-title">
      <div class="brand-lockup"><span class="brand-mark" aria-hidden="true">SM</span><div><strong>SIMUTU</strong><span>Sistem Survei Mutu LPMPP</span></div></div>
      <p class="eyebrow">Implementation foundation</p>
      <h1 id="login-title" tabindex="-1">Masuk ke sistem</h1>
      <p class="lede">Gunakan akun yang diberikan administrator. Sistem menggunakan session cookie; tidak ada token yang disimpan di browser storage.</p>
      <BaseAlert v-if="auth.error" tone="error" title="Login gagal"><p>{{ auth.error.message }}</p><small v-if="auth.error.requestId">Request ID: {{ auth.error.requestId }}</small></BaseAlert>
      <form class="login-form" @submit.prevent="submit">
        <FormField id="email" label="Email" :error="auth.error?.fields.email?.[0]">
          <template #default="{ describedBy }"><input id="email" v-model="form.email" type="email" autocomplete="username" required :aria-describedby="describedBy" /></template>
        </FormField>
        <FormField id="password" label="Kata sandi" :error="auth.error?.fields.password?.[0]">
          <template #default="{ describedBy }"><input id="password" v-model="form.password" type="password" autocomplete="current-password" required :aria-describedby="describedBy" /></template>
        </FormField>
        <label class="remember-field"><input v-model="form.remember" type="checkbox" /> Ingat sesi pada perangkat ini</label>
        <BaseButton type="submit" :loading="auth.loading">Masuk</BaseButton>
      </form>
    </section>
    <aside class="login-context" aria-label="Keamanan autentikasi"><p class="eyebrow">Fondasi keamanan</p><h2>Identitas dan scope diterapkan di server.</h2><ul class="feature-list"><li><ShieldCheck :size="18" aria-hidden="true" /> CSRF cookie sebelum login</li><li><ShieldCheck :size="18" aria-hidden="true" /> Permission dan scope organisasi</li><li><ShieldCheck :size="18" aria-hidden="true" /> Audit login/logout tanpa secret</li></ul></aside>
  </main>
</template>
