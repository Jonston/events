<script setup lang="ts">
import type { MasterCard } from '~/types'

defineProps<{ master: MasterCard }>()
</script>

<template>
  <NuxtLink
    :to="`/masters/${master.slug}`"
    class="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-forest-100 transition-[box-shadow,transform] duration-300 ease-out hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px] hover:shadow-forest-900/25"
  >
    <!-- ── Обложка ──
         Полоса, а не картинка на полкарточки: в списке решение принимают
         по имени и роду занятий, а обложка задаёт им тон. Её высота —
         ровно столько, чтобы аватар лёг на границу. -->
    <div class="relative h-28 shrink-0 overflow-hidden bg-forest-950">
      <img
        v-if="master.cover"
        :src="master.cover"
        alt=""
        loading="lazy"
        class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.04]"
      >
      <BotanicalScene
        v-else
        :seed="master.id"
        class="size-full transition-transform duration-500 ease-out group-hover:scale-[1.04]"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-forest-950/55 to-transparent" />
    </div>

    <div class="relative flex flex-1 flex-col px-5 pb-5">
      <!-- Аватар садится на стык обложки и тела карточки: он принадлежит
           человеку, а не фону, и белое кольцо отделяет его от снимка,
           каким бы тот ни был. -->
      <div class="absolute -top-9 left-5 size-18 overflow-hidden rounded-full bg-forest-100 ring-4 ring-white">
        <img
          v-if="master.photo"
          :src="master.photo"
          :alt="master.name"
          loading="lazy"
          class="size-full object-cover"
        >
        <BotanicalMark
          v-else
          class="absolute inset-0 m-auto size-2/3 text-forest-500"
          variant="seal"
        />
      </div>

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

      <p
        v-if="master.headline"
        class="mt-1.5 text-sm leading-relaxed text-stone-600"
      >
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
    </div>
  </NuxtLink>
</template>
