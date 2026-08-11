<script setup lang="ts">
import type { Time } from '@internationalized/date'

/**
 * Время в европейском виде: 18:30, без AM/PM. Наружу — строка `HH:mm`.
 */
const model = defineModel<string>({ default: '' })

withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
}>(), {
  size: 'lg',
  disabled: false
})

const value = computed({
  get: () => parseTime(model.value),
  set: (next: Time | undefined) => {
    model.value = formatTimeValue(next)
  }
})
</script>

<template>
  <UInputTime
    v-model="value"
    :hour-cycle="24"
    :size="size"
    :disabled="disabled"
    icon="i-lucide-clock"
  />
</template>
