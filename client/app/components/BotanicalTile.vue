<script setup lang="ts">
/**
 * Заставка кадра карточки: клевер на тонированном фоне.
 *
 * Отдельно от [BotanicalScene] — не потому, что там другой рисунок, а
 * потому, что там другая цена. В сцене четыре гауссовых размытия, и пока
 * лента карусели едет, браузер пересчитывает их на каждой открывшейся
 * полосе пикселей; шесть сцен в ряд отнимали половину кадров. Здесь нет
 * ни одного фильтра — только заливки и градиенты, а их растеризатор
 * рисует даром. В кадре ~450 px размытия всё равно не было видно: сцена
 * нарисована в системе на 1600 единиц и ужимается втрое с половиной.
 *
 * Глубина набирается не размытием, а прозрачностью и размером: дальний
 * лист крупный и бледный, ближний мелкий и плотный.
 */
const props = defineProps({
  // Тот же seed, что у сцены: одинаковый на сервере и клиенте, иначе
  // гидрация ругалась бы на расхождение.
  seed: { type: Number, default: 1 }
})

const uid = useId()

/** Детерминированный генератор: Math.random сломал бы SSR. */
function makeRandom(seed: number): () => number {
  let state = (seed * 2654435761) >>> 0

  return () => {
    state = (state + 0x6D2B79F5) >>> 0
    let t = Math.imul(state ^ (state >>> 15), 1 | state)
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t

    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

/**
 * Три листа по закреплённым местам, seed только качает их вокруг.
 * Свободный ход по всему кадру давал то пустой кадр, то ком в углу —
 * композиция не должна зависеть от того, какой id достался мастеру.
 *
 * Левый низ остаётся тихим: там из шапки выходит портрет.
 */
const clovers = computed(() => {
  const random = makeRandom(props.seed || 1)
  const shift = (span: number): number => (random() - 0.5) * span

  return [
    { key: 'far', x: 690, y: 130, scale: 2.1, opacity: 0.45, colorClass: 'text-forest-700' },
    { key: 'mid', x: 195, y: 92, scale: 1.2, opacity: 0.38, colorClass: 'text-forest-700' },
    { key: 'near', x: 505, y: 336, scale: 0.72, opacity: 0.3, colorClass: 'text-forest-600' }
  ].map(item => ({
    ...item,
    x: Math.round(item.x + shift(120)),
    y: Math.round(item.y + shift(60)),
    scale: +(item.scale * (0.88 + random() * 0.24)).toFixed(2),
    rotate: Math.round(shift(70))
  }))
})
</script>

<template>
  <svg
    viewBox="0 0 900 400"
    preserveAspectRatio="xMidYMid slice"
    aria-hidden="true"
    focusable="false"
  >
    <defs>
      <linearGradient
        :id="`tile-sky-${uid}`"
        x1="0.95"
        y1="0"
        x2="0.15"
        y2="1"
      >
        <stop
          offset="0"
          stop-color="#374f2e"
        />
        <stop
          offset="0.55"
          stop-color="#2c3e25"
        />
        <stop
          offset="1"
          stop-color="#1a2415"
        />
      </linearGradient>

      <!-- Свет там же, где в сцене: справа вверху. Карточка с фотографией
           и карточка с заставкой должны стоять в ряду как одно семейство. -->
      <radialGradient
        :id="`tile-glow-${uid}`"
        cx="0.76"
        cy="0.14"
        r="0.62"
      >
        <stop
          offset="0"
          stop-color="#c8d9bd"
          stop-opacity="0.4"
        />
        <stop
          offset="0.45"
          stop-color="#7ba068"
          stop-opacity="0.14"
        />
        <stop
          offset="1"
          stop-color="#5a8148"
          stop-opacity="0"
        />
      </radialGradient>

      <g :id="`clover-${uid}`">
        <path
          v-for="angle in CLOVER_ANGLES"
          :key="angle"
          :d="CLOVER_LOBE"
          :transform="`rotate(${angle})`"
          fill="currentColor"
        />
        <path
          :d="CLOVER_STEM"
          fill="none"
          stroke="currentColor"
          stroke-width="9"
          stroke-linecap="round"
        />
      </g>
    </defs>

    <rect
      width="900"
      height="400"
      :fill="`url(#tile-sky-${uid})`"
    />
    <rect
      width="900"
      height="400"
      :fill="`url(#tile-glow-${uid})`"
    />

    <use
      v-for="clover in clovers"
      :key="clover.key"
      :href="`#clover-${uid}`"
      :class="clover.colorClass"
      :opacity="clover.opacity"
      :transform="`translate(${clover.x} ${clover.y}) rotate(${clover.rotate}) scale(${clover.scale})`"
    />
  </svg>
</template>
