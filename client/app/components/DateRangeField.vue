<script setup lang="ts">
import type { CalendarDate } from '@internationalized/date'

/**
 * Даты проведения одним календарём на два месяца.
 *
 * Двумя отдельными полями человек выбирал вслепую: чтобы понять, попадает ли
 * мероприятие на выходные, приходилось открыть один календарь, запомнить,
 * закрыть, открыть второй. Здесь оба конца видны сразу, а между ними —
 * подсвеченный отрезок.
 */
const start = defineModel<string>('start', { default: '' })
const end = defineModel<string>('end', { default: '' })

withDefaults(defineProps<{ months?: number }>(), { months: 2 })

const range = computed({
  get: () => ({ start: parseDate(start.value), end: parseDate(end.value) }),
  set: (value: { start?: CalendarDate, end?: CalendarDate }) => {
    start.value = formatDateValue(value.start)
    end.value = formatDateValue(value.end)
  }
})
</script>

<template>
  <UCalendar
    v-model="range"
    range
    :number-of-months="months"
    :week-starts-on="1"
    fixed-weeks
    size="lg"
    class="rounded-lg border border-default p-3"
  />
</template>
