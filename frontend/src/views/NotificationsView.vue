<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'

import BaseAlert from '@/components/ui/BaseAlert.vue'
import type { AppNotification } from '@/services/phase13'
import { useNotificationsStore } from '@/stores/notifications'

const notifications = useNotificationsStore()
const { items, unread, loading, error } = storeToRefs(notifications)

async function load() {
  await notifications.load()
}

async function markRead(item: AppNotification) {
  await notifications.markRead(item)
}

onMounted(load)
</script>

<template>
  <section aria-labelledby="notifications-title">
    <div class="page-heading"><div><p class="eyebrow">Pusat aktivitas</p><h1 id="notifications-title" tabindex="-1">Notifikasi</h1><p>{{ unread }} belum dibaca. Pesan tidak memuat jawaban survei atau secret.</p></div><button v-if="unread" class="button button-secondary" type="button" @click="notifications.readAll">Baca semua</button></div>
    <p v-if="loading" role="status">Memuat notifikasi…</p>
    <BaseAlert v-else-if="error" tone="error" title="Notifikasi tidak dapat dimuat">{{ error }} <button class="button button-secondary" @click="load">Coba lagi</button></BaseAlert>
    <div v-else-if="items.length === 0" class="empty-state"><h2>Belum ada notifikasi</h2><p>Pembaruan yang relevan akan muncul di sini.</p></div>
    <ul v-else class="phase13-list" aria-live="polite">
      <li v-for="item in items" :key="item.id" :class="{ unread: !item.read_at }">
        <div><small>{{ item.type.replace(/_/g, ' ') }}</small><h2>{{ item.title }}</h2><p>{{ item.message }}</p><time :datetime="item.created_at">{{ new Date(item.created_at).toLocaleString('id-ID') }}</time></div>
        <div class="phase13-actions"><span v-if="item.read_at" class="notification-read-state">Telah dibaca</span><RouterLink v-if="item.route" class="button button-secondary" :to="item.route" @click="markRead(item)">Buka</RouterLink><button v-if="!item.read_at" class="button button-quiet" @click="markRead(item)">Tandai dibaca</button></div>
      </li>
    </ul>
  </section>
</template>
