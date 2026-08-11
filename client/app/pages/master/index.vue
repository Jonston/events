<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Обзор'
})

const { fetchOverview } = useMasterApi()
const { data } = await useAsyncData('master-overview', fetchOverview)

const overview = computed(() => data.value)

/**
 * Показываем то, на что можно отреагировать сегодня. «Всего мероприятий
 * за всё время» — красивое число, по которому нечего сделать.
 */
const tiles = computed(() => [
  {
    label: 'Новых заявок',
    value: overview.value?.newLeads ?? 0,
    icon: 'i-lucide-inbox',
    to: '/master/leads',
    accent: (overview.value?.newLeads ?? 0) > 0
  },
  {
    label: 'На витрине',
    value: overview.value?.published ?? 0,
    icon: 'i-lucide-calendar-days',
    to: '/master/events',
    accent: false
  },
  {
    label: 'Черновиков',
    value: overview.value?.drafts ?? 0,
    icon: 'i-lucide-file-pen-line',
    to: '/master/events',
    accent: false
  },
  {
    label: 'Оценка',
    value: overview.value?.rating ? overview.value.rating.value.toFixed(1) : '—',
    icon: 'i-lucide-star',
    to: '/master/reviews',
    accent: false
  }
])
</script>

<template>
  <div class="space-y-6">
    <UAlert
      v-if="overview && !overview.isActive"
      color="warning"
      variant="subtle"
      icon="i-lucide-eye-off"
      title="Ваша страница скрыта"
      description="Пока она не опубликована, вас нет в каталоге. Заполните её и включите показ."
      :actions="[{ label: 'Открыть страницу', to: '/master/profile', color: 'warning', variant: 'subtle' }]"
    />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <ULink
        v-for="tile in tiles"
        :key="tile.label"
        :to="tile.to"
        class="rounded-xl border p-4 transition-colors"
        :class="tile.accent ? 'border-primary/40 bg-primary/5' : 'border-default hover:bg-elevated/50'"
      >
        <div class="flex items-center gap-2 text-muted">
          <UIcon
            :name="tile.icon"
            class="size-4"
          />
          <span class="text-sm">{{ tile.label }}</span>
        </div>
        <p class="mt-1.5 font-display text-3xl text-highlighted">
          {{ tile.value }}
        </p>
      </ULink>
    </div>

    <UCard v-if="overview?.nextEvent">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-sm text-muted">
            Ближайшая дата
          </p>
          <p class="mt-1 font-display text-2xl text-highlighted">
            {{ overview.nextEvent.title }}
          </p>
          <p class="mt-1 text-sm text-muted">
            {{ formatDayTime(overview.nextEvent.startsAt) }}
            · {{ overview.nextEvent.leadsCount }}
            {{ plural(overview.nextEvent.leadsCount, 'заявка', 'заявки', 'заявок') }}
            <template v-if="overview.nextEvent.capacity">
              из {{ overview.nextEvent.capacity }} мест
            </template>
          </p>
        </div>

        <UButton
          :to="`/events/${overview.nextEvent.slug}`"
          target="_blank"
          color="neutral"
          variant="subtle"
          trailing-icon="i-lucide-external-link"
        >
          Как это видят участники
        </UButton>
      </div>
    </UCard>

    <div class="flex flex-wrap gap-3">
      <UButton
        to="/master/events/create"
        color="secondary"
        size="lg"
        icon="i-lucide-plus"
        class="rounded-full"
      >
        Новое мероприятие
      </UButton>
      <UButton
        to="/master/profile"
        color="neutral"
        variant="subtle"
        size="lg"
        icon="i-lucide-id-card"
      >
        Моя страница
      </UButton>
    </div>
  </div>
</template>
