<script setup lang="ts">
import type { EventCard } from '~/types'

withDefaults(defineProps<{
  event: EventCard
  showMaster?: boolean
}>(), { showMaster: true })
</script>

<template>
  <ShowcaseCard
    :to="`/events/${event.slug}`"
    :image="event.image"
    :avatar="event.masterPhoto"
    :alt="event.title"
    :seed="event.id"
  >
    <!-- Пустая строка на высоту аватара: он выступает из шапки, и без неё
         название начиналось бы под ним. -->
    <div class="min-h-9 pt-2" />

    <h3 class="mt-2 font-display text-xl text-forest-900 transition-colors group-hover:text-coral-700">
      {{ event.title }}
    </h3>

    <!-- Две строки под описание держатся всегда: без этого у карточки без
         описания дата вставала выше, чем у соседей по ряду. -->
    <p class="mt-1.5 line-clamp-2 min-h-11.5 text-sm leading-relaxed text-stone-600">
      {{ event.summary }}
    </p>

    <dl class="mt-3 space-y-1.5 text-sm text-stone-500">
      <div class="flex items-center gap-2">
        <dt class="sr-only">
          Даты
        </dt>
        <UIcon
          name="i-lucide-calendar"
          class="size-4 shrink-0 text-forest-500"
        />
        <dd>{{ formatRange(event.startsAt, event.endsAt) }}</dd>
      </div>
      <div
        v-if="event.location"
        class="flex items-center gap-2"
      >
        <dt class="sr-only">
          Место
        </dt>
        <UIcon
          name="i-lucide-map-pin"
          class="size-4 shrink-0 text-forest-500"
        />
        <dd>{{ event.location }}</dd>
      </div>
      <div
        v-if="showMaster && event.masterName"
        class="flex items-center gap-2"
      >
        <dt class="sr-only">
          Мастер
        </dt>
        <UIcon
          name="i-lucide-users"
          class="size-4 shrink-0 text-forest-500"
        />
        <dd>{{ event.masterName }}</dd>
      </div>
    </dl>

    <!--
      Цена на карточке намеренно: для высокого чека это самый сильный
      фильтр качества лида — она отсекает нецелевых до клика.
    -->
    <div class="mt-auto flex items-end justify-between gap-3 pt-4">
      <p class="font-display text-xl text-forest-900">
        {{ event.priceFormatted }}
      </p>
      <span class="inline-flex items-center gap-1.5 text-sm text-coral-700">
        Подробнее
        <UIcon
          name="i-lucide-arrow-right"
          class="size-4 transition-transform duration-300 ease-out group-hover:translate-x-1"
        />
      </span>
    </div>
  </ShowcaseCard>
</template>
