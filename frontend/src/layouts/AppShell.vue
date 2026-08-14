<script setup lang="ts">
import { LogOut, Menu, X } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import { navigationFor } from '@/navigation'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const drawerOpen = ref(false)
const navigation = computed(() => navigationFor(auth.user))
const homeDestination = computed(() => auth.user?.roles.includes('leader') ? '/app/analytics' : '/app')

async function logout() {
  await auth.logout()
  await router.replace('/login')
}

function focusMain() {
  document.getElementById('foundation-main')?.focus()
}
</script>

<template>
  <a class="skip-link" href="#foundation-main" @click.prevent="focusMain">Lewati ke konten utama</a>
  <div class="foundation-shell">
    <header class="foundation-topbar">
      <button class="icon-button foundation-menu" type="button" aria-label="Buka navigasi" @click="drawerOpen = true"><Menu /></button>
      <RouterLink class="brand-button" :to="homeDestination"><span class="brand-mark" aria-hidden="true">SM</span><span><strong>SIMUTU</strong><small>Implementation foundation</small></span></RouterLink>
      <div class="foundation-user"><span><strong>{{ auth.user?.name }}</strong><small>{{ auth.user?.roles.join(', ') }}</small></span><button class="button button-quiet foundation-topbar-logout" type="button" @click="logout"><LogOut :size="17" aria-hidden="true" /> Keluar</button></div>
    </header>
    <aside class="foundation-sidebar">
      <nav aria-label="Navigasi aplikasi">
        <p class="nav-label">Fondasi aplikasi</p>
        <template v-for="item in navigation" :key="item.to">
          <a v-if="item.external" :href="item.to">{{ item.label }} <span class="sr-only">(membuka panel administrasi)</span></a>
          <RouterLink v-else :to="item.to">{{ item.label }}</RouterLink>
        </template>
      </nav>
      <button class="button sidebar-logout" type="button" @click="logout"><LogOut :size="17" aria-hidden="true" /> Keluar</button>
    </aside>
    <main id="foundation-main" class="foundation-content" tabindex="-1"><RouterView /></main>

    <div v-if="drawerOpen" class="drawer-backdrop" @click.self="drawerOpen = false">
      <aside class="mobile-drawer" aria-label="Navigasi mobile">
        <div class="drawer-head"><strong>Menu</strong><button class="icon-button" type="button" aria-label="Tutup navigasi" @click="drawerOpen = false"><X /></button></div>
        <nav><template v-for="item in navigation" :key="item.to"><a v-if="item.external" :href="item.to">{{ item.label }}</a><RouterLink v-else :to="item.to" @click="drawerOpen = false">{{ item.label }}</RouterLink></template></nav>
      </aside>
    </div>
  </div>
</template>
