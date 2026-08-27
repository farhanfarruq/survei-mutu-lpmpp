import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { normalizeApiError } from '@/services/api'
import { phase13Api, type AppNotification } from '@/services/phase13'

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<AppNotification[]>([])
  const unread = ref(0)
  const loading = ref(false)
  const error = ref('')
  const unreadLabel = computed(() => (unread.value > 99 ? '99+' : String(unread.value)))

  async function load() {
    loading.value = true
    error.value = ''
    try {
      const result = await phase13Api.notifications()
      items.value = result.data
      unread.value = result.meta.unread
    } catch (caught) {
      error.value = normalizeApiError(caught).message
    } finally {
      loading.value = false
    }
  }

  async function markRead(item: AppNotification) {
    if (item.read_at) return
    const result = await phase13Api.readNotification(item.id)
    item.read_at = result.data.read_at
    unread.value = result.meta.unread
  }

  async function readAll() {
    const result = await phase13Api.readAllNotifications()
    const readAt = new Date().toISOString()
    items.value.forEach((item) => {
      item.read_at ??= readAt
    })
    unread.value = result.meta.unread
  }

  function reset() {
    items.value = []
    unread.value = 0
    error.value = ''
  }

  return { items, unread, unreadLabel, loading, error, load, markRead, readAll, reset }
})
