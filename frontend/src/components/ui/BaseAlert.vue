<script setup lang="ts">
withDefaults(defineProps<{ tone?: 'info' | 'error' | 'success'; title: string }>(), { tone: 'info' })
</script>

<template>
  <div class="base-alert" :class="`base-alert-${tone}`" :role="tone === 'error' ? 'alert' : 'status'">
    <strong>{{ title }}</strong><div><slot /></div>
  </div>
</template>
