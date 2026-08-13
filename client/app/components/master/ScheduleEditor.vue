<script setup lang="ts">
import type { EventSessionForm } from '~/types'

/**
 * Расписание мероприятия: дни берутся из дат проведения, а пункты человек
 * пишет внутри дня.
 *
 * Дни не заводятся вручную — они и есть даты мероприятия. Заводить их
 * отдельно значило бы позволить расписание на день, когда ничего
 * не происходит, и следить за расхождением вручную.
 */
const model = defineModel<EventSessionForm[]>({ required: true })

const props = defineProps<{
  /** Даты проведения: от них раскладываются дни. */
  startDate: string
  endDate: string
  error?: string
}>()

/**
 * Уже написанное — по дням, включая дни за пределами нынешних дат.
 *
 * Человек сдвигает даты туда-сюда, пока подбирает удобные, и терять при
 * каждом сдвиге всё написанное нельзя. Вернул дату обратно — вернулось
 * и расписание на неё.
 */
const written = ref(new Map<string, EventSessionForm[]>())

for (const session of model.value) {
  written.value.set(session.day, [...(written.value.get(session.day) ?? []), { ...session }])
}

/** Дни, которые мастер убрал руками: в эти даты у него ничего не идёт. */
const dropped = ref(new Set<string>())

const enabled = ref(model.value.length > 0)

/** Все даты мероприятия — от начала до конца включительно. */
const dates = computed(() => props.startDate ? daysBetween(props.startDate, props.endDate || props.startDate) : [])

const days = computed(() => dates.value
  .filter(date => !dropped.value.has(date))
  .map(date => ({ date, items: written.value.get(date) ?? [] })))

/** Даты мероприятия, которые убрали: их можно вернуть одним нажатием. */
const hidden = computed(() => dates.value.filter(date => dropped.value.has(date)))

/**
 * Наружу уходит плоский список — так его хранит сервер. Дни за пределами
 * дат в него не попадают: они остаются только в памяти формы.
 */
watch([days, enabled], () => {
  model.value = enabled.value
    ? days.value.flatMap(day => day.items.map(item => ({ ...item, day: day.date })))
    : []
}, { deep: true })

watch(enabled, (on) => {
  // Включили на пустом расписании — первый день получает один пункт,
  // чтобы было видно, что здесь заполняют.
  if (on && !model.value.length && days.value[0] && !days.value[0].items.length) {
    addItem(days.value[0].date)
  }
})

function itemsOf(date: string): EventSessionForm[] {
  if (!written.value.has(date)) written.value.set(date, [])

  return written.value.get(date)!
}

function addItem(date: string): void {
  const items = itemsOf(date)
  // Новый пункт начинается там, где кончился прошлый: расписание пишут
  // подряд, и выставлять время каждый раз с нуля — лишняя работа.
  const last = items.at(-1)

  items.push({
    day: date,
    startsAt: last?.endsAt ?? last?.startsAt ?? '10:00',
    endsAt: null,
    title: '',
    note: null
  })

  written.value = new Map(written.value)
}

function removeItem(date: string, index: number): void {
  itemsOf(date).splice(index, 1)
  written.value = new Map(written.value)
}

function dropDay(date: string): void {
  dropped.value = new Set(dropped.value).add(date)
}

function restoreDay(date: string): void {
  const next = new Set(dropped.value)
  next.delete(date)
  dropped.value = next
}
</script>

<template>
  <div class="space-y-4">
    <USwitch
      v-model="enabled"
      size="lg"
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

      <p
        v-if="!dates.length"
        class="rounded-lg border border-dashed border-default px-4 py-6 text-center text-sm text-muted"
      >
        Сначала выберите даты проведения — дни расписания появятся сами.
      </p>

      <div
        v-for="day in days"
        :key="day.date"
        class="rounded-lg border border-default p-4"
      >
        <div class="mb-3 flex items-center justify-between gap-3">
          <p class="font-medium text-highlighted">
            {{ labelDate(day.date) }}
            <span class="font-normal text-muted">· {{ formatWeekday(day.date) }}</span>
          </p>

          <UButton
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-lucide-eye-off"
            :aria-label="`Убрать день ${labelDate(day.date)}`"
            title="В этот день ничего не идёт"
            @click="dropDay(day.date)"
          />
        </div>

        <div class="space-y-2">
          <div
            v-for="(item, index) in day.items"
            :key="index"
            class="flex flex-col gap-2 sm:flex-row sm:items-center"
          >
            <TimeField
              v-model="item.startsAt"
              size="sm"
              class="sm:w-30"
            />
            <TimeField
              :model-value="item.endsAt ?? ''"
              size="sm"
              class="sm:w-30"
              @update:model-value="value => item.endsAt = value || null"
            />
            <UInput
              v-model="item.title"
              size="sm"
              class="flex-1"
              placeholder="Что происходит"
            />
            <UButton
              color="neutral"
              variant="ghost"
              size="sm"
              icon="i-lucide-x"
              aria-label="Убрать пункт"
              @click="removeItem(day.date, index)"
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
          @click="addItem(day.date)"
        >
          Добавить пункт
        </UButton>
      </div>

      <!-- Убранные дни не исчезают совсем: передумать — обычное дело. -->
      <div
        v-if="hidden.length"
        class="flex flex-wrap items-center gap-2 text-sm text-muted"
      >
        Убраны:
        <UButton
          v-for="date in hidden"
          :key="date"
          color="neutral"
          variant="subtle"
          size="xs"
          icon="i-lucide-rotate-ccw"
          @click="restoreDay(date)"
        >
          {{ labelDate(date) }}
        </UButton>
      </div>
    </template>
  </div>
</template>
