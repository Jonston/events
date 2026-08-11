<script setup lang="ts">
useHead({ title: 'Мероприятия' })

const { fetchEvents } = useShowcaseApi()
const { data } = await useAsyncData('events', fetchEvents)

const events = computed(() => data.value?.data ?? [])
</script>

<template>
  <div>
    <div class="border-b border-forest-100 bg-gradient-to-b from-forest-50/70 to-paper">
      <div class="mx-auto max-w-page px-6 py-11">
        <header class="max-w-2xl">
          <h1 class="text-balance font-display text-4xl tracking-tight text-forest-900 sm:text-5xl">
            Ближайшие мероприятия
          </h1>
          <p class="mt-3 text-lg leading-relaxed text-stone-600">
            Занятия, консультации и выездные ретриты с открытым набором. Стоимость
            указана полностью — участие подтверждается предоплатой.
          </p>
        </header>
      </div>
    </div>

    <div class="mx-auto max-w-page px-6 py-11">
      <div
        v-if="events.length"
        class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
      >
        <ShowcaseEventCard
          v-for="event in events"
          :key="event.id"
          :event="event"
        />
      </div>

      <div
        v-else
        class="rounded-3xl bg-white px-6 py-14 text-center ring-1 ring-forest-100"
      >
        <BotanicalMark
          class="mx-auto size-24 text-forest-300"
          variant="seal"
        />
        <h2 class="mt-5 font-display text-2xl text-forest-900">
          Набора пока нет
        </h2>
        <p class="mx-auto mt-2 max-w-sm text-stone-600">
          Ближайшие даты ещё не объявлены. Загляните позже или напишите мастеру
          напрямую — он сообщит о старте набора.
        </p>
      </div>
    </div>
  </div>
</template>
