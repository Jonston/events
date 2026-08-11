<script setup lang="ts">
import type { EventSessionForm } from '~/types'

/**
 * Программа по дням.
 *
 * Пункты хранятся плоским списком, а показываются днями: так их правит
 * человек — «в субботу с десяти», а не «элемент номер семь». День добавляется
 * целиком, потому что расписание пишут днями, а не отдельными строчками
 * с датой у каждой.
 */
const model = defineModel<EventSessionForm[]>({ required: true })

const props = defineProps<{
  /** Дата начала мероприятия: от неё предлагаем первый день программы. */
  startsAt: string
  error?: string
}>()

const days = computed(() => {
  const grouped = new Map<string, EventSessionForm[]>()

  for (const session of model.value) {
    const list = grouped.get(session.day) ?? []
    list.push(session)
    grouped.set(session.day, list)
  }

  return [...grouped.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([day, items]) => ({
      day,
      items: items.sort((a, b) => a.startsAt.localeCompare(b.startsAt))
    }))
})

/** Следующий день после последнего в программе, иначе — день начала. */
function nextDay(): string {
  const last = days.value.at(-1)?.day
  const base = last ? new Date(`${last}T00:00:00`) : new Date(props.startsAt || Date.now())

  if (last) base.setDate(base.getDate() + 1)

  return base.toISOString().slice(0, 10)
}

function addDay(): void {
  model.value = [...model.value, { day: nextDay(), startsAt: '10:00', endsAt: null, title: '', note: null }]
}

function addItem(day: string): void {
  // Новый пункт начинается там, где кончился прошлый: расписание пишут
  // подряд, и переставлять время каждый раз с нуля — лишняя работа.
  const last = days.value.find(group => group.day === day)?.items.at(-1)

  model.value = [...model.value, {
    day,
    startsAt: last?.endsAt ?? last?.startsAt ?? '10:00',
    endsAt: null,
    title: '',
    note: null
  }]
}

function removeItem(session: EventSessionForm): void {
  model.value = model.value.filter(item => item !== session)
}

function removeDay(day: string): void {
  model.value = model.value.filter(item => item.day !== day)
}

function moveDay(from: string, to: string): void {
  model.value = model.value.map(item => item.day === from ? { ...item, day: to } : item)
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="font-medium text-highlighted">
          Программа по дням
        </p>
        <p class="text-sm text-muted">
          Необязательно: у часовой встречи программы нет, у выездной — это главное на странице.
        </p>
      </div>
      <UButton
        color="neutral"
        variant="subtle"
        icon="i-lucide-calendar-plus"
        @click="addDay"
      >
        Добавить день
      </UButton>
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :description="error"
    />

    <div
      v-for="group in days"
      :key="group.day"
      class="rounded-lg border border-default p-4"
    >
      <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <DateField
            :model-value="group.day"
            size="sm"
            class="w-56"
            @update:model-value="value => moveDay(group.day, String(value))"
          />
          <span class="text-sm text-muted">{{ formatWeekday(group.day) }}</span>
        </div>

        <UButton
          color="error"
          variant="ghost"
          size="sm"
          icon="i-lucide-trash-2"
          :aria-label="`Удалить день ${group.day}`"
          @click="removeDay(group.day)"
        />
      </div>

      <div class="space-y-2">
        <div
          v-for="(item, index) in group.items"
          :key="index"
          class="flex flex-col gap-2 sm:flex-row sm:items-start"
        >
          <TimeField
            v-model="item.startsAt"
            size="sm"
            class="sm:w-32"
          />
          <TimeField
            :model-value="item.endsAt ?? ''"
            size="sm"
            class="sm:w-32"
            @update:model-value="value => item.endsAt = value || null"
          />
          <div class="flex-1 space-y-2">
            <UInput
              v-model="item.title"
              size="sm"
              class="w-full"
              placeholder="Что происходит"
            />
            <UInput
              :model-value="item.note ?? ''"
              size="sm"
              class="w-full"
              placeholder="Уточнение — необязательно"
              @update:model-value="value => item.note = String(value) || null"
            />
          </div>
          <UButton
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-lucide-x"
            aria-label="Убрать пункт"
            @click="removeItem(item)"
          />
        </div>
      </div>

      <UButton
        color="neutral"
        variant="ghost"
        size="sm"
        icon="i-lucide-plus"
        class="mt-2"
        @click="addItem(group.day)"
      >
        Добавить пункт
      </UButton>
    </div>

    <p
      v-if="!days.length"
      class="rounded-lg border border-dashed border-default px-4 py-6 text-center text-sm text-muted"
    >
      Программы пока нет.
    </p>
  </div>
</template>
