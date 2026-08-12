<script setup lang="ts">
import type { MasterLead } from '~/types'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Заявки'
})

const { leads, total, statuses, events, query, pending, setQuery, toggleSort, replace } = useLeadsList()

// Поле поиска не дёргает API на каждую букву: запрос уходит,
// когда человек перестал печатать.
const search = ref(query.value.search)
watch(search, useDebounceFn((value: string) => setQuery({ search: value }), 350))
watch(() => query.value.search, (value) => {
  if (value !== search.value) search.value = value
})

/*
 * «Любой» отмечается словом, а не пустой строкой: выпадающий список
 * считает пустое значение сбросом выбора и отказывается такой пункт
 * показывать. В адресе строки при этом нет вовсе — фильтр «любой»
 * это отсутствие фильтра.
 */
const ANY = 'any'

const statusFilter = computed({
  get: () => query.value.status || ANY,
  set: (value: string) => setQuery({ status: value === ANY ? '' : value })
})

const eventFilter = computed({
  get: () => String(query.value.eventId || ANY),
  set: (value: string) => setQuery({ eventId: value === ANY ? '' : value })
})

const page = computed({
  get: () => query.value.page,
  set: (value: number) => setQuery({ page: value })
})

const statusItems = computed(() => [{ value: ANY, label: 'Любой статус' }, ...statuses.value])
const eventItems = computed(() => [
  { value: ANY, label: 'Все мероприятия' },
  ...events.value.map(item => ({ value: String(item.value), label: item.label }))
])

const active = ref<MasterLead | null>(null)
const dialogOpen = ref(false)

function open(lead: MasterLead): void {
  active.value = lead
  dialogOpen.value = true
}
</script>

<template>
  <div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <UInput
        v-model="search"
        icon="i-lucide-search"
        placeholder="Имя, телефон, почта или текст"
        size="lg"
        class="w-full sm:w-80"
        :loading="pending"
      />

      <USelect
        v-model="statusFilter"
        :items="statusItems"
        value-key="value"
        label-key="label"
        size="lg"
        class="w-48"
        aria-label="Статус"
      />

      <USelect
        v-model="eventFilter"
        :items="eventItems"
        value-key="value"
        label-key="label"
        size="lg"
        class="w-64"
        aria-label="Мероприятие"
      />

      <p class="ms-auto text-sm text-muted">
        Всего: {{ total }}
      </p>
    </div>

    <MasterLeadsTable
      :leads="leads"
      :pending="pending"
      :sort="query.sort"
      :direction="query.direction"
      @sort="toggleSort"
      @open="open"
    />

    <div
      v-if="total > query.perPage"
      class="mt-4 flex justify-end"
    >
      <UPagination
        v-model:page="page"
        :items-per-page="query.perPage"
        :total="total"
      />
    </div>

    <MasterLeadStatusDialog
      v-model:open="dialogOpen"
      :lead="active"
      :statuses="statuses"
      @saved="replace"
    />
  </div>
</template>
