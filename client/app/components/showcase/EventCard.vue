<script setup lang="ts">
import type { EventCard } from '~/types'

withDefaults(defineProps<{
  event: EventCard
  showMaster?: boolean
}>(), { showMaster: true })
</script>

<template>
  <NuxtLink
    :to="`/events/${event.slug}`"
    class="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-forest-100 transition-[box-shadow,transform] duration-300 ease-out hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px] hover:shadow-forest-900/25"
  >
    <!-- Место под снимок. Пока его нет, ветвь на тонированном поле держит
         карточку как приём, а не как незаполненную дырку. -->
    <div class="relative aspect-4/3 overflow-hidden bg-forest-50">
      <img
        v-if="event.image"
        :src="event.image"
        :alt="event.title"
        loading="lazy"
        class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03]"
      >
      <BotanicalMark
        v-else
        class="absolute inset-0 m-auto size-3/4 text-forest-300"
        variant="seal"
      />
    </div>

    <div class="flex flex-1 flex-col p-5">
      <h3 class="font-display text-xl text-forest-900 transition-colors group-hover:text-coral-700">
        {{ event.title }}
      </h3>

      <p
        v-if="event.summary"
        class="mt-1.5 text-sm leading-relaxed text-stone-600"
      >
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
    </div>
  </NuxtLink>
</template>
