<script setup lang="ts">
/**
 * День и время одной парой. Наружу — строка `YYYY-MM-DDTHH:mm`,
 * ровно та, что ждёт сервер.
 */
const model = defineModel<string>({ default: '' })

const props = withDefaults(defineProps<{
  /** Время по умолчанию, когда день уже выбран, а часы ещё нет. */
  defaultTime?: string
  size?: 'sm' | 'md' | 'lg'
}>(), {
  defaultTime: '10:00',
  size: 'lg'
})

const parts = computed(() => splitDateTime(model.value))

const date = computed({
  get: () => parts.value.date,
  set: (value: string) => {
    // День без времени — не момент: подставляем разумное время сами,
    // чтобы человеку не приходилось трогать второе поле ради типового случая.
    model.value = joinDateTime(value, parts.value.time || props.defaultTime)
  }
})

const time = computed({
  get: () => parts.value.time,
  set: (value: string) => {
    model.value = joinDateTime(parts.value.date, value)
  }
})

function clear(): void {
  model.value = ''
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <DateField
      v-model="date"
      :size="size"
      class="min-w-52 flex-1"
    />
    <TimeField
      v-model="time"
      :size="size"
      :disabled="!date"
      class="w-32"
    />
    <UButton
      v-if="model"
      color="neutral"
      variant="ghost"
      :size="size === 'lg' ? 'md' : 'xs'"
      icon="i-lucide-x"
      aria-label="Очистить"
      @click="clear"
    />
  </div>
</template>
