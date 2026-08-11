<script setup lang="ts">
definePageMeta({
  // Роли здесь нет намеренно: кабинет участника открыт любому вошедшему.
  // Мастер и администратор — тоже участники площадки, и запирать от них
  // собственные записи не за что.
  middleware: 'sanctum:auth',
  layout: 'cabinet',
  pageTitle: 'Обзор'
})

const { currentUser } = useAuth()

const sections = [
  { label: 'Мои записи', icon: 'i-lucide-ticket', text: 'Куда вы записались, когда начало и сколько осталось доплатить.' },
  { label: 'Мои отзывы', icon: 'i-lucide-message-square-quote', text: 'Оценки мастерам, у которых вы занимались.' },
  { label: 'Стать мастером', icon: 'i-lucide-sparkles', text: 'Заявка на свою страницу: рассмотрим и откроем витрину.' }
]
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-3">
      <UAvatar
        :src="currentUser?.avatarUrl ?? undefined"
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

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <UCard
        v-for="section in sections"
        :key="section.label"
        class="opacity-60"
      >
        <div class="flex items-start gap-3">
          <UIcon
            :name="section.icon"
            class="mt-0.5 size-5 shrink-0 text-primary"
          />
          <div>
            <p class="font-medium text-highlighted">
              {{ section.label }}
            </p>
            <p class="mt-1 text-sm text-muted">
              {{ section.text }}
            </p>
            <p class="mt-1.5 text-sm text-dimmed">
              В разработке
            </p>
          </div>
        </div>
      </UCard>
    </div>

    <UButton
      to="/events"
      color="secondary"
      size="lg"
      trailing-icon="i-lucide-arrow-right"
      class="rounded-full"
    >
      Посмотреть мероприятия
    </UButton>
  </div>
</template>
