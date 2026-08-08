<script setup lang="ts">
defineProps<{ id: string; label: string; error?: string; hint?: string }>()
</script>

<template>
  <label :for="id" class="form-field">
    <span>{{ label }}</span>
    <slot :described-by="error ? `${id}-error` : hint ? `${id}-hint` : undefined" />
    <small v-if="hint && !error" :id="`${id}-hint`">{{ hint }}</small>
    <small v-if="error" :id="`${id}-error`" class="field-error">{{ error }}</small>
  </label>
</template>
