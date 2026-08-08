<script setup lang="ts">
import { onMounted, ref } from 'vue'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import { normalizeApiError } from '@/services/api'
import { phase13Api, type AppNotification } from '@/services/phase13'

const items = ref<AppNotification[]>([])
const unread = ref(0)
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    const result = await phase13Api.notifications()
    items.value = result.data
    unread.value = result.meta.unread
  } catch (caught) { error.value = normalizeApiError(caught).message }
  finally { loading.value = false }
}

async function markRead(item: AppNotification) {
  if (item.read_at) return
  await phase13Api.readNotification(item.id)
  item.read_at = new Date().toISOString()
  unread.value = Math.max(0, unread.value - 1)
}

onMounted(load)
</script>

<template>
  <section aria-labelledby="notifications-title">
    <div class="page-heading"><div><p class="eyebrow">Pusat aktivitas</p><h1 id="notifications-title" tabindex="-1">Notifikasi</h1><p>{{ unread }} belum dibaca. Pesan tidak memuat jawaban survei atau secret.</p></div></div>
    <p v-if="loading" role="status">Memuat notifikasi…</p>
    <BaseAlert v-else-if="error" tone="error" title="Notifikasi tidak dapat dimuat">{{ error }} <button class="button button-secondary" @click="load">Coba lagi</button></BaseAlert>
    <div v-else-if="items.length === 0" class="empty-state"><h2>Belum ada notifikasi</h2><p>Pembaruan yang relevan akan muncul di sini.</p></div>
    <ul v-else class="phase13-list" aria-live="polite">
      <li v-for="item in items" :key="item.id" :class="{ unread: !item.read_at }">
        <div><small>{{ item.type.replaceAll('_', ' ') }}</small><h2>{{ item.title }}</h2><p>{{ item.message }}</p><time :datetime="item.created_at">{{ new Date(item.created_at).toLocaleString('id-ID') }}</time></div>
        <div class="phase13-actions"><RouterLink v-if="item.route" class="button button-secondary" :to="item.route" @click="markRead(item)">Buka</RouterLink><button v-if="!item.read_at" class="button button-quiet" @click="markRead(item)">Tandai dibaca</button></div>
      </li>
    </ul>
  </section>
</template>
