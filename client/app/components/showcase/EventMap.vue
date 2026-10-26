<script setup lang="ts">
import { LngLatBounds, Map as MapLibreMap, Marker, NavigationControl, Popup, setWorkerUrl } from 'maplibre-gl'
// Карта разбирает тайлы в веб-воркере и грузит его отдельным файлом. Сама
// она собирает путь от своего адреса — в сборке такого файла рядом нет,
// и без этой строки карта молча остаётся пустой.
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'
import type { EventCard } from '~/types'

/**
 * Мероприятия точками на карте.
 *
 * Списком видно, что происходит; картой — где. Для выездной программы это
 * первый вопрос после даты: «далеко ли ехать» из строки адреса не читается,
 * а из расстояния между точками читается сразу.
 *
 * Мероприятия без координат карту не получают — метка без точки встала бы
 * посреди океана. Список ниже показывает их наравне с остальными.
 */
const props = defineProps<{ events: EventCard[] }>()

// Киев по умолчанию: показывать пустой глобус бессмысленно, а большинство
// мероприятий площадки — здесь и рядом.
const FALLBACK = { lat: 50.4501, lng: 30.5234 }
const SINGLE_ZOOM = 12

const key = useRuntimeConfig().public.maptilerKey as string

const container = ref<HTMLElement | null>(null)
const map = shallowRef<MapLibreMap | null>(null)
const failed = ref<string | null>(null)

const points = computed(() => props.events.filter(event => event.lat !== null && event.lng !== null))

function pin(): HTMLElement {
  const element = document.createElement('div')

  element.className = 'cursor-pointer text-coral-700 drop-shadow-md'
  element.innerHTML = `<svg width="30" height="30" viewBox="0 0 24 24" aria-hidden="true">
    <path fill="currentColor" d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z" />
  </svg>`

  return element
}

/** Разметка выноски: карточку сюда не вставить — попап живёт вне Vue. */
function card(event: EventCard): string {
  const escape = (value: string) => value.replace(/[&<>"]/g, symbol =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[symbol] ?? symbol)

  return `<a href="/events/${escape(event.slug)}" class="block max-w-56 p-1 no-underline">
    <p class="font-display text-base text-forest-900">${escape(event.title)}</p>
    <p class="mt-1 text-sm text-stone-600">${escape(formatRange(event.startsAt, event.endsAt))}</p>
    ${event.location ? `<p class="mt-0.5 text-sm text-stone-500">${escape(event.location)}</p>` : ''}
    <p class="mt-1.5 font-medium text-coral-700">${escape(event.priceFormatted)}</p>
  </a>`
}

function draw(instance: MapLibreMap): void {
  const bounds = new LngLatBounds()

  for (const event of points.value) {
    const at: [number, number] = [event.lng!, event.lat!]

    new Marker({ element: pin(), anchor: 'bottom' })
      .setLngLat(at)
      .setPopup(new Popup({ offset: 26, closeButton: false }).setHTML(card(event)))
      .addTo(instance)

    bounds.extend(at)
  }

  if (points.value.length === 1) {
    instance.setCenter(bounds.getCenter()).setZoom(SINGLE_ZOOM)
  } else if (points.value.length > 1) {
    // Поля под метку: она рисуется вверх от точки, и без запаса верхняя
    // из них упиралась бы в край карты.
    instance.fitBounds(bounds, { padding: { top: 64, bottom: 40, left: 40, right: 40 }, maxZoom: 13, duration: 0 })
  }
}

function createMap(): void {
  if (!container.value || map.value) return

  if (!key) {
    failed.value = 'Карта не настроена: нет ключа MapTiler.'

    return
  }

  setWorkerUrl(maplibreWorkerUrl)

  const instance = new MapLibreMap({
    container: container.value,
    style: `https://api.maptiler.com/maps/streets-v2/style.json?key=${key}`,
    center: [FALLBACK.lng, FALLBACK.lat],
    zoom: 5,
    // Страницу листают колесом, и карта посреди неё не должна перехватывать
    // прокрутку: зум — с Ctrl, кнопками и щипком.
    cooperativeGestures: true,
    // Свои подсказки: штатные приходят по-английски.
    locale: {
      'CooperativeGesturesHandler.WindowsHelpText': 'Ctrl и колесо мыши — чтобы приблизить',
      'CooperativeGesturesHandler.MacHelpText': '⌘ и колесо мыши — чтобы приблизить',
      'CooperativeGesturesHandler.MobileHelpText': 'Двигайте карту двумя пальцами',
      'NavigationControl.ZoomIn': 'Приблизить',
      'NavigationControl.ZoomOut': 'Отдалить'
    }
  })

  instance.addControl(new NavigationControl({ showCompass: false }), 'top-right')
  instance.on('load', () => draw(instance))

  map.value = instance
}

onMounted(createMap)

onBeforeUnmount(() => {
  map.value?.remove()
  map.value = null
})
</script>

<template>
  <div
    v-if="points.length"
    class="relative h-96 overflow-hidden rounded-2xl ring-1 ring-forest-100 lg:h-[32rem]"
  >
    <div
      ref="container"
      class="size-full"
    />

    <p
      v-if="failed"
      class="absolute inset-0 flex items-center justify-center bg-forest-50 px-6 text-center text-stone-600"
    >
      {{ failed }}
    </p>
  </div>
</template>

<style>
/* Карта приносит свою гарнитуру и раздаёт её выноскам: без этого подпись
   в них набрана не тем шрифтом, что подписи вокруг. */
.maplibregl-map {
  font-family: inherit;
}

/* Выноска приходит со своим белым фоном и стрелкой — подгоняем под карточки
   страницы, чтобы она не выглядела чужой. */
.maplibregl-popup-content {
  border-radius: 0.75rem;
  padding: 0.75rem 0.875rem;
  box-shadow: 0 12px 28px -12px oklch(0.28 0.03 145 / 0.35);
}

.maplibregl-popup-anchor-bottom .maplibregl-popup-tip {
  border-top-color: white;
}
</style>
