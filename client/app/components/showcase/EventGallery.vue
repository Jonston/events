<script setup lang="ts">
import type { Photo } from '~/types'

/**
 * Снимки мероприятия на странице программы: обложка и галерея одной лентой.
 *
 * Один снимок показывается как снимок, а не как карусель из одного слайда:
 * стрелки, точки и подглядывающие соседи в этом случае обещают листание,
 * которого нет.
 *
 * Кадр здесь всегда обрезан под 4:3 — по нажатию он открывается целиком,
 * этим занимается страница: лайтбокс перекрывает её весь, и держать его
 * внутри карусели значило бы прятать общий для страницы слой в частности.
 */
defineProps<{
  photos: Photo[]
  title: string
}>()

const emit = defineEmits<{ open: [index: number] }>()
</script>

<template>
  <div
    v-if="photos.length === 1"
    class="overflow-hidden rounded-3xl"
  >
    <button
      type="button"
      class="block w-full"
      :aria-label="`Открыть снимок во весь экран`"
      @click="emit('open', 0)"
    >
      <img
        :src="photos[0]!.full"
        :alt="title"
        class="aspect-4/3 w-full object-cover"
      >
    </button>
  </div>

  <!--
    Соседние снимки выглядывают по краям намеренно: это единственное, что
    без подписи и без анимации говорит «здесь есть ещё» — точки под кадром
    видит только тот, кто уже досмотрел до низа.

    Отсюда и настройки: слайд уже контейнера, выравнивание по центру и
    contain-scroll выключен — иначе первый и последний кадры прижимаются
    к краю и подглядывание на них пропадает.
  -->
  <UCarousel
    v-else-if="photos.length"
    v-slot="{ item, index }"
    :items="photos"
    arrows
    dots
    loop
    class-names
    align="center"
    :contain-scroll="false"
    :ui="{
      item: 'basis-[82%] sm:basis-[72%] lg:basis-[66%] opacity-45 transition-opacity duration-500 ease-out [&.is-snapped]:opacity-100',
      prev: 'start-3 sm:start-4 size-11 bg-white/90 text-forest-800 ring-1 ring-forest-100 shadow-[0_6px_18px_-8px] shadow-forest-900/40 backdrop-blur-sm hover:bg-white hover:text-coral-700 disabled:bg-white/70',
      next: 'end-3 sm:end-4 size-11 bg-white/90 text-forest-800 ring-1 ring-forest-100 shadow-[0_6px_18px_-8px] shadow-forest-900/40 backdrop-blur-sm hover:bg-white hover:text-coral-700 disabled:bg-white/70',
      dots: 'inset-x-0 bottom-4 gap-2',
      dot: 'size-2.5 bg-white/60 ring-1 ring-forest-900/10 transition-[background-color,width] data-[state=active]:w-6 data-[state=active]:bg-white'
    }"
  >
    <button
      type="button"
      class="block w-full"
      :aria-label="`Открыть снимок ${index + 1} во весь экран`"
      @click="emit('open', index)"
    >
      <img
        :src="(item as Photo).full"
        :alt="`${title} — снимок ${index + 1}`"
        :loading="index === 0 ? 'eager' : 'lazy'"
        draggable="false"
        class="aspect-4/3 w-full select-none rounded-3xl object-cover"
      >
    </button>
  </UCarousel>
</template>
