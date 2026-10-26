<script setup lang="ts" generic="T extends { id: number }">
/**
 * Лента карточек витрины: мероприятия на главной, мастера, ближайшие даты
 * на страницах мастера и мероприятия. Раньше каждый экран раскладывал
 * карточки своей сеткой, и одно и то же выглядело разными списками.
 *
 * Лента идёт по колонке страницы, стрелки — за её пределами, в поле. Поле
 * получается само: отступ считается как половина того, что осталось от
 * страницы, но не меньше места под кнопку, поэтому на узком экране лента
 * ужимается, а кнопкам всё равно есть где стоять.
 */
withDefaults(defineProps<{
  items: T[]
  title?: string
  /** Куда ведёт кнопка справа; без адреса её не будет. */
  to?: string
  toLabel?: string
}>(), { title: '', toLabel: 'Смотреть все' })

defineSlots<{ default(props: { item: T }): unknown }>()

/** Поле по бокам — оно же место кнопки, поэтому число одно на оба. */
const GUTTER = 'px-[max(4.25rem,calc((100%-var(--container-page))/2+1.5rem))]'
</script>

<template>
  <PageSection
    v-if="items.length"
    width="full"
  >
    <div
      v-if="title || to"
      class="flex flex-wrap items-center justify-between gap-4"
      :class="GUTTER"
    >
      <h2
        v-if="title"
        class="font-display text-3xl tracking-tight text-forest-900 sm:text-4xl"
      >
        {{ title }}
      </h2>

      <UButton
        v-if="to"
        :to="to"
        color="primary"
        trailing-icon="i-lucide-arrow-right"
        class="ms-auto rounded-full"
      >
        {{ toLabel }}
      </UButton>
    </div>

    <!--
      `grow` — на случай, когда карточек меньше, чем помещается: они разбирают
      свободное место между собой, вместо того чтобы оставить пустую четверть.

      `align="start"` — карточка встаёт по левому краю поля: при выравнивании
      по центру ряд после каждого нажатия съезжал. Ход короче штатного:
      на длинной прокрутке лента докатывалась до места заметно позже пальца.
    -->
    <div :class="GUTTER">
      <UCarousel
        v-slot="{ item }"
        v-bind="carouselArrowsQuiet"
        :items="items"
        arrows
        align="start"
        :duration="18"
        :ui="{
          root: title || to ? 'mt-5' : '',
          container: 'items-stretch',
          item: 'grow basis-full sm:basis-1/2 lg:basis-1/3 xl:basis-1/4',
          prev: `-start-14 sm:-start-14 ${SHAPE}`,
          next: `-end-14 sm:-end-14 ${SHAPE}`
        }"
      >
        <slot :item="item" />
      </UCarousel>
    </div>
  </PageSection>
</template>
