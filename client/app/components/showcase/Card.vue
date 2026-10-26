<script setup lang="ts">
/**
 * Карточка витрины: снимок сверху, содержимое снизу.
 *
 * Одна на мастера и на мероприятие. Раньше это были две разные карточки —
 * у мастера полоса с портретом на стыке, у мероприятия кадр во всю ширину, —
 * и стоя рядом на главной они читались как элементы двух разных сайтов.
 * Различается только тело, поэтому оно приходит слотом.
 *
 * Кадр — 9:4 от ширины карточки. Пробовал делить высоту долями (2 к 3):
 * в ряду карточки тянутся до самой длинной, и доли множили её запас — одна
 * многословная подпись раздувала снимки во всём ряду, а под короткими
 * оставалось поле пустоты в пол-экрана. Фиксированное соотношение держит
 * все кадры ряда одной высоты, а разница подписей остаётся разницей
 * подписей.
 */
withDefaults(defineProps<{
  to: string
  /** Снимок шапки. Без него место держит ботанический рисунок. */
  image?: string | null
  /** Портрет на стыке шапки и тела; без него аватара не будет вовсе. */
  avatar?: string | null
  alt?: string
  /** Отличает ботанические заглушки друг от друга. */
  seed?: number
}>(), { image: null, avatar: null, alt: '', seed: 1 })
</script>

<template>
  <NuxtLink
    :to="to"
    class="group flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-forest-100 transition-shadow duration-300 ease-out hover:shadow-[0_12px_28px_-12px] hover:shadow-forest-900/25"
  >
    <div class="relative aspect-9/4 shrink-0 overflow-hidden bg-forest-950">
      <img
        v-if="image"
        :src="image"
        :alt="alt"
        loading="lazy"
        class="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.04]"
      >
      <BotanicalTile
        v-else
        :seed="seed"
        class="size-full transition-transform duration-500 ease-out group-hover:scale-[1.04]"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-forest-950/55 to-transparent" />
    </div>

    <div class="relative flex flex-1 flex-col px-5 pb-5">
      <!-- Портрет садится на стык: он принадлежит человеку, а не фону,
           и белое кольцо отделяет его от снимка, каким бы тот ни был. -->
      <div class="absolute -top-9 left-5 size-18 overflow-hidden rounded-full bg-forest-100 ring-[3px] ring-white">
        <img
          v-if="avatar"
          :src="avatar"
          :alt="alt"
          loading="lazy"
          class="size-full object-cover"
        >
        <BotanicalMark
          v-else
          class="absolute inset-0 m-auto size-2/3 text-forest-500"
          variant="seal"
        />
      </div>

      <slot />
    </div>
  </NuxtLink>
</template>
