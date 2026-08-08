<script setup lang="ts">
import { computed } from 'vue'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const primaryUnit = computed(() => auth.user?.organizational_units.find((unit) => unit.is_primary) ?? auth.user?.organizational_units[0])
</script>

<template>
  <div class="page-heading"><div><p class="eyebrow">Implementation foundation</p><h1 tabindex="-1">Selamat datang, {{ auth.user?.name }}</h1><p class="lede">Autentikasi, permission, dan scope organisasi pada halaman ini berasal dari backend.</p></div></div>
  <section class="kpi-grid"><article class="kpi-card"><span>Status akun</span><strong class="foundation-kpi-text">Aktif</strong><small>Session cookie terverifikasi</small></article><article class="kpi-card"><span>Peran</span><strong class="foundation-kpi-text">{{ auth.user?.roles[0] ?? '—' }}</strong><small>{{ auth.user?.permissions.length ?? 0 }} permission efektif</small></article><article class="kpi-card"><span>Unit utama</span><strong class="foundation-kpi-text">{{ primaryUnit?.code ?? '—' }}</strong><small>{{ primaryUnit?.name ?? 'Belum ditetapkan' }}</small></article></section>
  <section class="panel"><p class="eyebrow">Batas Phase 09</p><h2>Fondasi siap untuk modul berikutnya</h2><p>Belum ada builder survei production, pengumpulan respons, analitik, AI, atau laporan. Fitur tersebut tetap berada pada phase implementasi terpisah.</p></section>
</template>
