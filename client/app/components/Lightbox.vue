<script setup lang="ts">
import type { Photo } from '~/types'

/**
 * Просмотр снимка во весь экран.
 *
 * Кадр в сетке всегда обрезан квадратом — лайтбокс существует ровно затем,
 * чтобы показать его целиком, поэтому картинка вписывается в экран,
 * а не заполняет его.
 */
const props = defineProps<{ items: Photo[] }>()

/** null — закрыт, число — индекс открытого снимка. */
const index = defineModel<number | null>({ default: null })

const current = computed(() => index.value === null ? null : (props.items[index.value] ?? null))
const hasMany = computed(() => props.items.length > 1)

function close(): void {
  index.value = null
}

function go(step: number): void {
  if (index.value === null || !props.items.length) return

  // По кругу: на последнем кадре «дальше» упирается в стену, и человек
  // решает, что галерея сломалась, а не что она кончилась.
  index.value = (index.value + step + props.items.length) % props.items.length
}

function onKey(event: KeyboardEvent): void {
  if (event.key === 'Escape') close()
  if (event.key === 'ArrowRight') go(1)
  if (event.key === 'ArrowLeft') go(-1)
}

// Фон не должен уезжать под лайтбоксом: на телефоне это единственный способ
// отличить пролистывание галереи от прокрутки страницы под ней.
watch(() => index.value !== null, (open) => {
  if (import.meta.server) return

  document.body.style.overflow = open ? 'hidden' : ''

  if (open) {
    window.addEventListener('keydown', onKey)
  } else {
    window.removeEventListener('keydown', onKey)
  }
})

onBeforeUnmount(() => {
  if (import.meta.server) return

  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKey)
})
</script>

<template>
  <Teleport
    v-if="current"
    to="body"
  >
    <div
      class="on-dark fixed inset-0 z-50 flex flex-col bg-forest-950/95 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-label="Просмотр галереи"
      @click.self="close"
    >
      <!-- ── Шапка ── -->
      <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <p class="text-sm text-forest-200">
          <span
            v-if="hasMany"
            class="tabular-nums"
          >{{ (index ?? 0) + 1 }} / {{ items.length }}</span>
        </p>
        <!-- `ghost` давал тёмный значок на почти чёрном фоне: кнопка была
             на месте, но найти её удавалось только курсором. -->
        <UButton
          v-bind="carouselArrow"
          icon="i-lucide-x"
          autofocus
          aria-label="Закрыть просмотр"
          @click="close"
        />
      </div>

      <!-- ── Кадр ── -->
      <div
        class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-4 sm:px-16"
        @click.self="close"
      >
        <UButton
          v-if="hasMany"
          v-bind="carouselArrow"
          class="absolute left-1 top-1/2 -translate-y-1/2 sm:left-4"
          icon="i-lucide-chevron-left"
          aria-label="Предыдущий снимок"
          @click="go(-1)"
        />

        <img
          :src="current.full"
          alt=""
          class="max-h-full min-h-0 w-auto max-w-full rounded-xl object-contain shadow-2xl shadow-black/50"
        >

        <UButton
          v-if="hasMany"
          v-bind="carouselArrow"
          class="absolute right-1 top-1/2 -translate-y-1/2 sm:right-4"
          icon="i-lucide-chevron-right"
          aria-label="Следующий снимок"
          @click="go(1)"
        />
      </div>

      <!-- ── Лента миниатюр ── -->
      <div
        v-if="hasMany"
        class="flex shrink-0 gap-2 overflow-x-auto px-4 pb-4 sm:justify-center sm:px-6"
      >
        <button
          v-for="(item, position) in items"
          :key="position"
          type="button"
          class="h-14 w-20 shrink-0 overflow-hidden rounded-lg transition-opacity"
          :class="position === index ? 'ring-2 ring-white' : 'opacity-50 hover:opacity-100'"
          :aria-label="`Открыть снимок ${position + 1}`"
          @click="index = position"
        >
          <img
            :src="item.thumb"
            alt=""
            class="size-full object-cover"
          >
        </button>
      </div>
    </div>
  </Teleport>
</template>
