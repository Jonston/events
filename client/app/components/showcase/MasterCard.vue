<script setup lang="ts">
import type { MasterCard } from '~/types'

defineProps<{ master: MasterCard }>()
</script>

<template>
  <ShowcaseCard
    :to="`/masters/${master.slug}`"
    :image="master.cover"
    :avatar="master.photo"
    :alt="master.name"
    :seed="master.id"
  >
    <!-- Рейтинг стоит на уровне аватара, справа: так он читается
         как свойство человека, а не как ещё одна строка описания. -->
    <div class="flex min-h-9 items-center justify-end pt-2">
      <span
        v-if="master.rating"
        class="inline-flex items-center gap-1.5 text-sm"
      >
        <RatingStars
          :value="master.rating.value"
          :size="14"
          class="text-coral-600"
        />
        <span class="font-medium tabular-nums text-forest-900">{{ master.rating.value.toFixed(1) }}</span>
        <span class="text-stone-500">({{ master.rating.count }})</span>
      </span>
    </div>

    <h3 class="mt-2 font-display text-xl text-forest-900 transition-colors group-hover:text-coral-700">
      {{ master.name }}
    </h3>

    <p class="mt-1.5 line-clamp-2 min-h-11.5 text-sm leading-relaxed text-stone-600">
      {{ master.headline }}
    </p>

    <div class="mt-auto flex items-end justify-between gap-3 pt-4 text-sm text-stone-500">
      <span
        v-if="master.city"
        class="inline-flex items-center gap-1.5"
      >
        <UIcon
          name="i-lucide-map-pin"
          class="size-4 shrink-0 text-forest-500"
        />
        {{ master.city }}
      </span>
      <span
        v-if="master.upcomingEventsCount"
        class="inline-flex items-center gap-1.5"
      >
        <UIcon
          name="i-lucide-calendar"
          class="size-4 shrink-0 text-forest-500"
        />
        {{ master.upcomingEventsCount }} {{ plural(master.upcomingEventsCount, 'дата', 'даты', 'дат') }}
      </span>
    </div>
  </ShowcaseCard>
</template>
