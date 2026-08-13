<script setup lang="ts">
import { Map as MapLibreMap, NavigationControl, setWorkerUrl } from 'maplibre-gl'
// Карта разбирает тайлы в веб-воркере и грузит его отдельным файлом.
// Сама она собирает путь к нему от своего адреса — в сборке такого файла
// рядом нет, и без этой строки карта молча остаётся пустой: стиль и тайлы
// приходят с кодом 200, а рисовать их некому.
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'
import 'maplibre-gl/dist/maplibre-gl.css'

/**
 * Выбор места на карте: двигается карта, а не точка.
 *
 * Точка приколота к центру и не двигается никогда — человек подводит под неё
 * карту. На телефоне это единственный способ поставить метку точно:
 * перетаскиваемый маркер там всё время под пальцем, и последние метры
 * выставляются вслепую.
 */
const address = defineModel<string>('address', { default: '' })
const latitude = defineModel<number | null>('latitude', { default: null })
const longitude = defineModel<number | null>('longitude', { default: null })

const props = defineProps<{
  /**
   * Вкладка с картой открыта. Скрытая вкладка — это display:none, где
   * у контейнера нулевой размер: карта, созданная в нём, рисует пустое
   * полотно и сама об этом не узнаёт.
   */
  active?: boolean
  error?: string
}>()

// Киев по умолчанию: показывать пустой глобус бессмысленно, а большинство
// мероприятий площадки — здесь и рядом.
const FALLBACK = { lat: 50.4501, lng: 30.5234 }
const DEFAULT_ZOOM = 11
const PICKED_ZOOM = 15

const key = useRuntimeConfig().public.maptilerKey as string

const container = ref<HTMLElement | null>(null)
const map = shallowRef<MapLibreMap | null>(null)

const query = ref(address.value)
const suggestions = ref<Array<{ label: string, lat: number, lng: number }>>([])
const searching = ref(false)
const resolving = ref(false)
const failed = ref<string | null>(null)
/** Карта в движении — булавка приподнята над своей тенью. */
const dragging = ref(false)

let searchTimer: ReturnType<typeof setTimeout> | null = null
let draggedByHand = false
/** Строку только что подставила карта — подсказки на это не отвечают. */
let filledByMap = false

const hasPoint = computed(() => latitude.value !== null && longitude.value !== null)
const coords = computed(() => hasPoint.value
  ? `${Number(latitude.value).toFixed(5)}, ${Number(longitude.value).toFixed(5)}`
  : null)

function geocodingUrl(path: string, params: Record<string, string> = {}): string {
  return `https://api.maptiler.com/geocoding/${path}.json?${new URLSearchParams({ key, language: 'ru', ...params })}`
}

/** Строка адреса — в список мест с координатами. */
async function search(term: string) {
  if (!key || term.trim().length < 3) {
    suggestions.value = []

    return
  }

  searching.value = true

  try {
    const result = await $fetch<{ features: Array<{ place_name: string, center: [number, number] }> }>(
      geocodingUrl(encodeURIComponent(term), { limit: '5' })
    )

    suggestions.value = result.features.map(feature => ({
      label: feature.place_name,
      lng: feature.center[0],
      lat: feature.center[1]
    }))
  } catch {
    suggestions.value = []
  } finally {
    searching.value = false
  }
}

watch(query, (term) => {
  if (filledByMap) {
    filledByMap = false

    return
  }

  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => search(term), 350)
})

/** Координаты — в адрес. Запускается только после перетаскивания руками. */
async function resolveAddress(lat: number, lng: number) {
  if (!key) return

  resolving.value = true

  try {
    const result = await $fetch<{ features: Array<{ place_name: string }> }>(
      geocodingUrl(`${lng},${lat}`, { limit: '1' })
    )

    const found = result.features[0]?.place_name

    if (found) {
      filledByMap = true
      address.value = found
      query.value = found
      suggestions.value = []
    }
  } catch {
    // Молча: адрес человек может вписать и руками, а сообщение об ошибке
    // геокодера ему ничего не объясняет.
  } finally {
    resolving.value = false
  }
}

function pick(item: { label: string, lat: number, lng: number }) {
  address.value = item.label
  query.value = item.label
  latitude.value = item.lat
  longitude.value = item.lng
  suggestions.value = []

  map.value?.easeTo({ center: [item.lng, item.lat], zoom: PICKED_ZOOM })
}

function clearPoint() {
  latitude.value = null
  longitude.value = null
}

function createMap() {
  if (!container.value || map.value) return

  if (!key) {
    failed.value = 'Карта не настроена: нет ключа MapTiler. Адрес можно вписать вручную.'

    return
  }

  setWorkerUrl(maplibreWorkerUrl)

  const instance = new MapLibreMap({
    container: container.value,
    style: `https://api.maptiler.com/maps/streets-v2/style.json?key=${key}`,
    center: [longitude.value ?? FALLBACK.lng, latitude.value ?? FALLBACK.lat],
    zoom: hasPoint.value ? PICKED_ZOOM : DEFAULT_ZOOM,
    // Зум к центру, а не к курсору: иначе колесо мыши уводило бы
    // выбранную точку в сторону.
    scrollZoom: { around: 'center' }
  })

  instance.addControl(new NavigationControl({ showCompass: false }), 'top-right')

  instance.on('dragstart', () => {
    draggedByHand = true
  })

  // Не только перетаскивание: булавка поднимается и на зуме, и на переезде
  // к найденному адресу — карта движется, точка ещё не выбрана.
  instance.on('movestart', () => {
    dragging.value = true
  })

  instance.on('moveend', () => {
    dragging.value = false

    const center = instance.getCenter()
    latitude.value = Number(center.lat.toFixed(6))
    longitude.value = Number(center.lng.toFixed(6))

    if (draggedByHand) {
      draggedByHand = false
      resolveAddress(center.lat, center.lng)
    }
  })

  map.value = instance
}

onMounted(() => {
  if (props.active !== false) createMap()
})

// Карта создаётся, когда вкладка открылась: в скрытом контейнере у неё
// нулевой размер, и полотно осталось бы пустым.
watch(() => props.active, (active) => {
  if (!active) return

  if (!map.value) {
    nextTick(createMap)
  } else {
    nextTick(() => map.value?.resize())
  }
})

onBeforeUnmount(() => {
  if (searchTimer) clearTimeout(searchTimer)
  map.value?.remove()
  map.value = null
})
</script>

<template>
  <!-- Две половины, как и на остальных вкладках: слева адрес и то,
       что выбрано, справа сама карта. -->
  <div class="grid gap-8 lg:grid-cols-2">
    <FormSection
      title="Адрес"
      description="Начните вводить — подскажем и поставим точку на карте."
    >
      <UFormField
        name="location"
        :error="error"
      >
        <div class="relative">
          <UInput
            v-model="query"
            size="lg"
            class="w-full"
            placeholder="Киев, ул. Рейтарская, 12"
            :loading="searching || resolving"
            @blur="address = query"
          />

          <ul
            v-if="suggestions.length"
            class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-default bg-default shadow-lg"
          >
            <li
              v-for="item in suggestions"
              :key="item.label"
              class="cursor-pointer px-4 py-2.5 text-sm transition-colors hover:bg-elevated"
              @mousedown.prevent="pick(item)"
            >
              {{ item.label }}
            </li>
          </ul>
        </div>
      </UFormField>

      <div class="flex flex-wrap items-center gap-3 text-sm">
        <span
          v-if="coords"
          class="inline-flex items-center gap-1.5 text-muted"
        >
          <UIcon
            name="i-lucide-crosshair"
            class="size-4"
          />
          <span class="tabular-nums">{{ coords }}</span>
        </span>
        <span
          v-else
          class="text-dimmed"
        >
          Точка не выбрана — на странице мероприятия карты не будет.
        </span>

        <UButton
          v-if="hasPoint"
          color="neutral"
          variant="ghost"
          size="xs"
          icon="i-lucide-x"
          @click="clearPoint"
        >
          Убрать точку
        </UButton>
      </div>
    </FormSection>

    <FormSection
      title="Точка на карте"
      description="Подведите карту так, чтобы булавка стояла на месте встречи."
    >
      <UAlert
        v-if="failed"
        color="warning"
        variant="subtle"
        icon="i-lucide-map-pin-off"
        :description="failed"
      />

      <div
        v-else
        class="relative h-112 overflow-hidden rounded-lg border border-default"
      >
        <div
          ref="container"
          class="size-full"
        />

        <!-- Булавка приколота к центру: она никуда не двигается, двигается
             карта. Тень лежит ровно в выбранной точке, булавка приподнята
             над ней на высоту иглы — пока карту тащат, выше и с тенью
             пошире, чтобы было видно, что точка ещё не поставлена. -->
        <div
          class="pointer-events-none absolute inset-0 flex items-center justify-center"
          aria-hidden="true"
        >
          <span
            class="absolute h-1.5 rounded-full bg-forest-950/30 transition-all"
            :class="dragging ? 'w-4 opacity-100' : 'w-3 opacity-70'"
          />
          <svg
            width="34"
            height="34"
            viewBox="0 0 24 24"
            class="absolute text-coral-700 drop-shadow-md transition-transform duration-150"
            :class="dragging ? '-translate-y-5' : '-translate-y-3.5'"
          >
            <path
              fill="currentColor"
              d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"
            />
          </svg>
        </div>
      </div>
    </FormSection>
  </div>
</template>
