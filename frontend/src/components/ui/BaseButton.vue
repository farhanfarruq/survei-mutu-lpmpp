<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'primary' | 'secondary' | 'quiet'; loading?: boolean; disabled?: boolean; type?: 'button' | 'submit' }>(), {
  variant: 'primary',
  loading: false,
  disabled: false,
  type: 'button',
})
</script>

<template>
  <button class="button" :class="`button-${variant}`" :type="type" :disabled="loading || disabled" :aria-busy="loading">
    <span v-if="loading">Memproses…</span><slot v-else />
  </button>
</template>
