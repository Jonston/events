<script setup lang="ts">
import type { CalendarDate } from '@internationalized/date'

/**
 * Выбор дня календарём. Наружу — строка `YYYY-MM-DD`.
 *
 * Календарь живёт в поповере, а не стоит на странице: в форме он занимал бы
 * треть экрана ради значения, которое меняют один раз.
 */
const model = defineModel<string>({ default: '' })

withDefaults(defineProps<{
  placeholder?: string
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
}>(), {
  placeholder: 'Выберите дату',
  size: 'lg',
  disabled: false
})

const open = ref(false)

const calendarValue = computed({
  get: () => parseDate(model.value),
  set: (value: CalendarDate | undefined) => {
    model.value = formatDateValue(value)
    // Закрываем сразу: день выбран, держать календарь открытым незачем.
    open.value = false
  }
})
</script>

<template>
  <UPopover v-model:open="open">
    <UButton
      color="neutral"
      variant="outline"
      :size="size"
      :disabled="disabled"
      icon="i-lucide-calendar"
      class="w-full justify-start font-normal"
      :class="model ? '' : 'text-dimmed'"
    >
      {{ model ? labelDate(model) : placeholder }}
    </UButton>

    <template #content>
      <UCalendar
        v-model="calendarValue"
        :week-starts-on="1"
        fixed-weeks
        class="p-2"
      />
    </template>
  </UPopover>
</template>
