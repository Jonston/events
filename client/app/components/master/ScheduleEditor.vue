<script setup lang="ts">
import type { EventSessionForm } from '~/types'

/**
 * Расписание мероприятия: дни, а внутри дней — пункты.
 *
 * Наружу уходит плоский список — так его хранит сервер, — но правится
 * он днями: человек пишет «в субботу с десяти», а не «элемент номер семь».
 * День здесь существует сам по себе, даже пустой: иначе «добавить день»
 * вынужден сразу создавать пункт, и кнопка делает не то, что обещает.
 */
const model = defineModel<EventSessionForm[]>({ required: true })

const props = defineProps<{
  /** Дата начала мероприятия: от неё предлагаем первый день. */
  startsAt: string
  error?: string
}>()

interface Day {
  key: number
  date: string
  items: EventSessionForm[]
}

let nextKey = 1

/** Плоский список с сервера — в дни. */
function toDays(sessions: EventSessionForm[]): Day[] {
  const grouped = new Map<string, EventSessionForm[]>()

  for (const session of sessions) {
    grouped.set(session.day, [...(grouped.get(session.day) ?? []), session])
  }

  return [...grouped.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([date, items]) => ({ key: nextKey++, date, items }))
}

const days = ref<Day[]>(toDays(model.value))

/**
 * Переключатель отвечает на вопрос «есть ли у мероприятия программа».
 * У часовой встречи её нет, и незачем показывать пустой конструктор дней
 * каждому, кто открыл вкладку.
 */
const enabled = ref(days.value.length > 0)

// Наружу отдаём плоский список: пустые дни в нём просто не появляются.
watch(days, (value) => {
  model.value = value.flatMap(day => day.items.map(item => ({ ...item, day: day.date })))
}, { deep: true })

watch(enabled, (on) => {
  if (!on) {
    days.value = []

    return
  }

  if (!days.value.length) addDay()
})

/**
 * Дата местная, а не UTC: `toISOString()` переводит полночь в часовом поясе
 * восточнее Гринвича на предыдущие сутки, и «добавить день» возвращало бы
 * ту же дату, что уже есть.
 */
function isoDate(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${date.getFullYear()}-${month}-${day}`
}

/** Следующий день после последнего, иначе — день начала мероприятия. */
function nextDate(): string {
  const last = days.value.at(-1)?.date
  const base = last ? new Date(`${last}T00:00:00`) : new Date(props.startsAt || Date.now())

  if (last) base.setDate(base.getDate() + 1)

  return isoDate(base)
}

function addDay(): void {
  days.value.push({ key: nextKey++, date: nextDate(), items: [] })
}

function removeDay(index: number): void {
  days.value.splice(index, 1)
}

function addItem(day: Day): void {
  // Новый пункт начинается там, где кончился прошлый: расписание пишут
  // подряд, и выставлять время каждый раз с нуля — лишняя работа.
  const last = day.items.at(-1)

  day.items.push({
    day: day.date,
    startsAt: last?.endsAt ?? last?.startsAt ?? '10:00',
    endsAt: null,
    title: '',
    note: null
  })
}

function removeItem(day: Day, index: number): void {
  day.items.splice(index, 1)
}
</script>

<template>
  <div class="space-y-4">
    <USwitch
      v-model="enabled"
      label="Расписание по дням"
      description="У часовой встречи его нет, у выездной программы — это главное на странице."
    />

    <template v-if="enabled">
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        icon="i-lucide-circle-alert"
        :description="error"
      />

      <div
        v-for="(day, dayIndex) in days"
        :key="day.key"
        class="rounded-lg border border-default p-4"
      >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <DateField
              v-model="day.date"
              size="sm"
              class="w-56"
            />
            <span class="text-sm text-muted">{{ formatWeekday(day.date) }}</span>
          </div>

          <UButton
            color="error"
            variant="ghost"
            size="sm"
            icon="i-lucide-trash-2"
            :aria-label="`Удалить день ${dayIndex + 1}`"
            @click="removeDay(dayIndex)"
          />
        </div>

        <div class="space-y-2">
          <div
            v-for="(item, index) in day.items"
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
              @click="removeItem(day, index)"
            />
          </div>

          <p
            v-if="!day.items.length"
            class="text-sm text-dimmed"
          >
            В этот день пока ничего не назначено.
          </p>
        </div>

        <UButton
          color="neutral"
          variant="ghost"
          size="sm"
          icon="i-lucide-plus"
          class="mt-2"
          @click="addItem(day)"
        >
          Добавить пункт
        </UButton>
      </div>

      <!-- Кнопка внизу, а не в шапке: день добавляют, дописав предыдущий,
           и рука уже там. -->
      <UButton
        color="neutral"
        variant="subtle"
        icon="i-lucide-calendar-plus"
        @click="addDay"
      >
        Добавить день
      </UButton>
    </template>
  </div>
</template>
