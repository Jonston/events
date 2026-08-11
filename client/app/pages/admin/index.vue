<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'admin',
  layout: 'cabinet',
  pageTitle: 'Обзор'
})

const { currentUser } = useAuth()

/**
 * Заготовка витрины: разделы, которые появятся на следующих этапах.
 * Пока показывают только маршрут, а не цифры — считать ещё нечего.
 */
const sections = [
  { label: 'Пользователи', icon: 'i-lucide-users', to: '/admin/users' },
  { label: 'Мастера', icon: 'i-lucide-sparkles', to: null },
  { label: 'Мероприятия', icon: 'i-lucide-calendar-days', to: null },
  { label: 'Заявки', icon: 'i-lucide-inbox', to: null }
]
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <UAvatar
        :alt="currentUser?.name"
        size="lg"
      />
      <div>
        <p class="font-semibold text-highlighted">
          {{ currentUser?.name }}
        </p>
        <p class="text-sm text-muted">
          {{ currentUser?.email }}
        </p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <UCard
        v-for="section in sections"
        :key="section.label"
        :class="section.to ? '' : 'opacity-60'"
      >
        <div class="flex items-center gap-3">
          <UIcon
            :name="section.icon"
            class="size-5 shrink-0 text-primary"
          />
          <div>
            <p class="font-medium text-highlighted">
              {{ section.label }}
            </p>
            <ULink
              v-if="section.to"
              :to="section.to"
              class="text-sm text-primary"
            >
              Открыть
            </ULink>
            <p
              v-else
              class="text-sm text-dimmed"
            >
              В разработке
            </p>
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>
