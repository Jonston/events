<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { MasterEvent } from '~/types'

const props = defineProps<{ events: MasterEvent[] }>()
const emit = defineEmits<{ remove: [event: MasterEvent] }>()

const search = ref('')
const state = ref<'all' | 'published' | 'draft'>('all')
const period = ref<'all' | 'upcoming' | 'past'>('upcoming')

const stateOptions = [
  { value: 'all', label: 'Все' },
  { value: 'published', label: 'На витрине' },
  { value: 'draft', label: 'Черновики' }
]

const periodOptions = [
  { value: 'upcoming', label: 'Предстоящие' },
  { value: 'past', label: 'Прошедшие' },
  { value: 'all', label: 'За всё время' }
]

function isPast(event: MasterEvent): boolean {
  return new Date(event.startsAt) < new Date()
}

const rows = computed(() => {
  const term = search.value.trim().toLowerCase()

  return props.events.filter((event) => {
    if (state.value === 'published' && !event.isPublished) return false
    if (state.value === 'draft' && event.isPublished) return false
    if (period.value === 'upcoming' && isPast(event)) return false
    if (period.value === 'past' && !isPast(event)) return false

    if (!term) return true

    return event.title.toLowerCase().includes(term)
      || (event.location ?? '').toLowerCase().includes(term)
  })
})

// Фильтры сообщают, что список неполон: иначе пустая таблица читается
// как «мероприятий нет», хотя они просто отфильтрованы.
const filtered = computed(() => rows.value.length !== props.events.length)

function reset(): void {
  search.value = ''
  state.value = 'all'
  period.value = 'all'
}

const columns: TableColumn<MasterEvent>[] = [
  { accessorKey: 'title', header: 'Мероприятие' },
  { accessorKey: 'startsAt', header: 'Когда' },
  { accessorKey: 'location', header: 'Место' },
  { accessorKey: 'price', header: 'Стоимость' },
  { accessorKey: 'leadsCount', header: 'Заявки' },
  { accessorKey: 'seatsLeft', header: 'Места' },
  // Заголовок непустой намеренно. Пустая строка не даёт серверу ни одного
  // узла, а на клиенте Vue создаёт под неё якоря фрагмента — разметка
  // расходится, и таблица перерисовывается заново после гидрации.
  // Заодно колонка перестаёт быть безымянной для скринридера.
  { id: 'actions', header: 'Действия' }
]
</script>

<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <UInput
        v-model="search"
        icon="i-lucide-search"
        placeholder="Название или место"
        size="lg"
        class="w-full sm:w-72"
      />
      <USelect
        v-model="state"
        :items="stateOptions"
        value-key="value"
        label-key="label"
        size="lg"
        class="w-40"
        aria-label="Состояние"
      />
      <USelect
        v-model="period"
        :items="periodOptions"
        value-key="value"
        label-key="label"
        size="lg"
        class="w-44"
        aria-label="Период"
      />

      <UButton
        v-if="filtered"
        color="neutral"
        variant="ghost"
        icon="i-lucide-filter-x"
        @click="reset"
      >
        Сбросить
      </UButton>

      <p class="ml-auto text-sm text-muted">
        Показано {{ rows.length }} из {{ events.length }}
      </p>
    </div>

    <UTable
      :data="rows"
      :columns="columns"
    >
      <template #title-cell="{ row }">
        <div class="flex items-center gap-3">
          <div class="size-11 shrink-0 overflow-hidden rounded-md bg-elevated">
            <img
              v-if="row.original.image"
              :src="row.original.image"
              alt=""
              class="size-full object-cover"
            >
            <BotanicalMark
              v-else
              class="size-full p-1.5 text-forest-300"
              variant="seal"
            />
          </div>
          <div class="min-w-0">
            <NuxtLink
              :to="`/master/events/${row.original.id}`"
              class="font-medium text-highlighted hover:text-primary"
            >
              {{ row.original.title }}
            </NuxtLink>
            <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
              <UBadge
                :color="row.original.isPublished ? 'success' : 'neutral'"
                variant="subtle"
                size="sm"
              >
                {{ row.original.isPublished ? 'На витрине' : 'Черновик' }}
              </UBadge>
              <UBadge
                v-if="isPast(row.original)"
                color="neutral"
                variant="subtle"
                size="sm"
              >
                Прошло
              </UBadge>
              <span
                v-if="row.original.sessions.length"
                class="text-sm text-muted"
              >
                программа на {{ new Set(row.original.sessions.map(s => s.day)).size }}
                {{ plural(new Set(row.original.sessions.map(s => s.day)).size, 'день', 'дня', 'дней') }}
              </span>
            </div>
          </div>
        </div>
      </template>

      <template #startsAt-cell="{ row }">
        <span class="whitespace-nowrap">{{ formatRange(row.original.startsAt, row.original.endsAt) }}</span>
      </template>

      <template #location-cell="{ row }">
        <span class="text-muted">{{ row.original.location ?? '—' }}</span>
      </template>

      <template #price-cell="{ row }">
        <span class="whitespace-nowrap">{{ row.original.priceFormatted }}</span>
      </template>

      <template #leadsCount-cell="{ row }">
        <span :class="row.original.leadsCount ? '' : 'text-dimmed'">
          {{ row.original.leadsCount || '—' }}
        </span>
      </template>

      <!-- Занято/всего, а не «осталось»: мастеру важнее набор, чем остаток. -->
      <template #seatsLeft-cell="{ row }">
        <span
          v-if="row.original.capacity === null"
          class="text-dimmed"
        >без лимита</span>
        <span
          v-else
          :class="row.original.seatsLeft === 0 ? 'text-warning' : ''"
        >
          {{ row.original.seatsTaken }} / {{ row.original.capacity }}
        </span>
      </template>

      <template #actions-header>
        <span class="sr-only">Действия</span>
      </template>

      <template #actions-cell="{ row }">
        <div class="flex justify-end gap-1">
          <UButton
            v-if="row.original.isPublished"
            :to="`/events/${row.original.slug}`"
            target="_blank"
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-lucide-external-link"
            aria-label="Посмотреть на витрине"
          />
          <UButton
            :to="`/master/events/${row.original.id}`"
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-lucide-pencil"
            aria-label="Редактировать"
          />
          <UButton
            color="error"
            variant="ghost"
            size="sm"
            icon="i-lucide-trash-2"
            aria-label="Удалить"
            @click="emit('remove', row.original)"
          />
        </div>
      </template>

      <template #empty>
        <div class="py-12 text-center">
          <p class="text-muted">
            {{ filtered ? 'Под фильтры ничего не подошло.' : 'Мероприятий пока нет.' }}
          </p>
          <UButton
            v-if="filtered"
            color="neutral"
            variant="ghost"
            class="mt-2"
            @click="reset"
          >
            Сбросить фильтры
          </UButton>
        </div>
      </template>
    </UTable>
  </div>
</template>
