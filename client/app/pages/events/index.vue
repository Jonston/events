<script setup lang="ts">
useHead({ title: 'Мероприятия' })

const { fetchEvents } = useShowcaseApi()
const { data } = await useAsyncData('events', fetchEvents)

const events = computed(() => data.value?.data ?? [])
</script>

<template>
  <div>
    <PageSection class="border-b border-forest-100 bg-gradient-to-b from-forest-50/70 to-paper py-11">
      <header class="max-w-2xl">
        <h1 class="text-balance font-display text-4xl tracking-tight text-forest-900 sm:text-5xl">
          Ближайшие мероприятия
        </h1>
        <p class="mt-3 text-lg leading-relaxed text-stone-600">
          Занятия, консультации и выездные ретриты с открытым набором. Стоимость
          указана полностью — участие подтверждается предоплатой.
        </p>
      </header>
    </PageSection>

    <!-- Карта первой, список под ней: «где» на этой странице — вопрос
         наравне с «когда», а из строки адреса расстояние не читается.
         Лентой здесь не обойтись: каталог показывает всё сразу. -->
    <PageSection
      v-if="events.length"
      class="py-9"
    >
      <ShowcaseEventMap :events="events" />

      <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <ShowcaseEventCard
          v-for="event in events"
          :key="event.id"
          :event="event"
        />
      </div>
    </PageSection>

    <PageSection
      v-else
      class="py-11"
    >
      <div class="rounded-3xl bg-white px-6 py-14 text-center ring-1 ring-forest-100">
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
    </PageSection>
  </div>
</template>
