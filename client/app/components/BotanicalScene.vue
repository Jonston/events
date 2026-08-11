<script setup lang="ts">
/**
 * Фон шапки для профилей без обложки.
 *
 * Это не узор, а сцена: свет в правом верхнем углу, силуэты листвы,
 * наползающие с краёв, и чистая тёмная середина внизу — там ляжет имя.
 * Композиция построена именно так, потому что читаемость текста не должна
 * зависеть от того, что нарисовано за ним.
 *
 * Расположение веток разводится по seed: три профиля подряд с одинаковой
 * шапкой выглядят как незагрузившаяся картинка, а не как приём.
 */

const props = defineProps({
  // Обычно id мастера — одинаковый seed даёт одинаковую сцену между
  // сервером и клиентом, иначе гидрация ругалась бы на расхождение.
  seed: { type: Number, default: 1 }
})

const uid = useId()

/** Детерминированный генератор: Math.random сломал бы SSR. */
function makeRandom(seed: number): () => number {
  let state = (seed * 2654435761) >>> 0

  return () => {
    state = (state + 0x6d2b79f5) >>> 0
    let t = Math.imul(state ^ (state >>> 15), 1 | state)
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t

    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

function round(value: number): number {
  return Math.round(value * 10) / 10
}

/** Точка и касательная на кубической кривой — по ним сажаются листья. */
type Point = [number, number]
/** Кубическая кривая — ровно четыре опорные точки, не «сколько-то». */
type Cubic = [Point, Point, Point, Point]
/** Ось: 0 — x, 1 — y. Именно так, а не number, — тогда обращение
    к точке проверяется на этапе сборки, а не падает в рантайме. */
type Axis = 0 | 1
/** Отрезок «от — до», из которого берётся случайное значение. */
type Range = [number, number]

function onCurve(points: Cubic, t: number) {
  const [p0, p1, p2, p3] = points
  const u = 1 - t

  const at = (i: Axis): number => u * u * u * p0[i] + 3 * u * u * t * p1[i] + 3 * u * t * t * p2[i] + t * t * t * p3[i]
  const slope = (i: Axis): number => 3 * u * u * (p1[i] - p0[i]) + 6 * u * t * (p2[i] - p1[i]) + 3 * t * t * (p3[i] - p2[i])

  const dx = slope(0)
  const dy = slope(1)
  const len = Math.hypot(dx, dy) || 1

  return { x: at(0), y: at(1), ux: dx / len, uy: dy / len }
}

/**
 * Ветвь: стебель и листья попарно вдоль него. Лист несимметричен —
 * одна сторона полнее другой, иначе он читается как аптечный логотип.
 */
interface FrondOptions {
  length: number
  bend: number
  leafCount: number
  leafLength: number
  leafWidth: number
  spread: number
}

function buildFrond({ length, bend, leafCount, leafLength, leafWidth, spread }: FrondOptions) {
  const points: Cubic = [
    [0, 0],
    [bend * 0.1, -length * 0.36],
    [bend * 0.55, -length * 0.74],
    [bend, -length]
  ]

  const stem = `M0 0C${round(points[1][0])} ${round(points[1][1])} ${round(points[2][0])} ${round(points[2][1])} ${round(bend)} ${round(-length)}`

  const leaves = []

  for (let i = 0; i < leafCount; i++) {
    const t = 0.16 + (i / leafCount) * 0.78
    const base = onCurve(points, t)
    // Ближе к верхушке лист мельче — так ветвь сужается, а не обрывается.
    const size = leafLength * (1 - t * 0.55)
    const width = leafWidth * (1 - t * 0.5)

    for (const side of [1, -1]) {
      const angle = side * spread
      const cos = Math.cos(angle)
      const sin = Math.sin(angle)
      const dx = base.ux * cos - base.uy * sin
      const dy = base.ux * sin + base.uy * cos
      const px = -dy
      const py = dx

      const tip: Point = [base.x + dx * size, base.y + dy * size]
      const c1: Point = [base.x + dx * size * 0.3 + px * width, base.y + dy * size * 0.3 + py * width]
      const c2: Point = [base.x + dx * size * 0.72 + px * width * 0.62, base.y + dy * size * 0.72 + py * width * 0.62]
      const c3: Point = [base.x + dx * size * 0.7 - px * width * 0.16, base.y + dy * size * 0.7 - py * width * 0.16]
      const c4: Point = [base.x + dx * size * 0.28 - px * width * 0.24, base.y + dy * size * 0.28 - py * width * 0.24]

      leaves.push(
        `M${round(base.x)} ${round(base.y)}`
        + `C${round(c1[0])} ${round(c1[1])} ${round(c2[0])} ${round(c2[1])} ${round(tip[0])} ${round(tip[1])}`
        + `C${round(c3[0])} ${round(c3[1])} ${round(c4[0])} ${round(c4[1])} ${round(base.x)} ${round(base.y)}Z`
      )
    }
  }

  return { stem, leaves }
}

const fronds = [
  buildFrond({ length: 300, bend: 54, leafCount: 7, leafLength: 74, leafWidth: 21, spread: 1.02 }),
  buildFrond({ length: 250, bend: -84, leafCount: 6, leafLength: 88, leafWidth: 27, spread: 0.86 }),
  buildFrond({ length: 340, bend: 26, leafCount: 9, leafLength: 62, leafWidth: 16, spread: 1.15 }),
  buildFrond({ length: 210, bend: 96, leafCount: 5, leafLength: 96, leafWidth: 34, spread: 0.7 }),
  buildFrond({ length: 380, bend: -40, leafCount: 11, leafLength: 54, leafWidth: 14, spread: 1.28 })
]

/**
 * Три плана глубины вокруг источника света справа вверху.
 *
 * Листва намеренно сбита в кучи по краям, а не разложена ровным шагом:
 * равномерный шаг читается как обои, а куча — как заросли. Левый низ
 * остаётся пустым и тёмным — туда ложится имя, и спорить с ним фону нечем.
 */
const layers = computed(() => {
  const random = makeRandom(props.seed || 1)
  const pick = (min: number, max: number): number => min + random() * (max - min)

  let counter = 0

  /** Разброс куста: пары «от» и «до» по каждому свойству. */
  interface ClusterSpec {
    x: Range
    y: Range
    rotate: Range
    scale: Range
  }

  const cluster = (count: number, spec: ClusterSpec) => Array.from({ length: count }, () => ({
    key: counter++,
    variant: Math.floor(random() * fronds.length),
    x: round(pick(spec.x[0], spec.x[1])),
    y: round(pick(spec.y[0], spec.y[1])),
    rotate: round(pick(spec.rotate[0], spec.rotate[1])),
    scale: round(pick(spec.scale[0], spec.scale[1])),
    // Зеркалим часть веток: иначе повторно использованная геометрия
    // выдаёт себя одинаковым изгибом у соседей.
    flip: random() < 0.42 ? -1 : 1
  }))

  return [
    {
      key: 'far',
      colorClass: 'text-forest-400',
      opacity: 0.15,
      blur: 14,
      items: [
        // Огромная мягкая масса слева: она не читается как ветка,
        // но снимает плоскость с той половины кадра, где нет света.
        ...cluster(2, { x: [-220, 300], y: [-40, 130], rotate: [150, 214], scale: [2.8, 3.8] }),
        ...cluster(4, { x: [340, 1700], y: [-70, 120], rotate: [144, 216], scale: [1.7, 2.7] })
      ]
    },
    {
      // Теневая половина кадра. Здесь листва светлее фона, а не темнее:
      // силуэт цвета фона на фоне — это пустое место, а не силуэт.
      key: 'shade',
      colorClass: 'text-forest-700',
      opacity: 0.5,
      blur: 5,
      items: cluster(3, { x: [-160, 420], y: [-80, 30], rotate: [154, 206], scale: [1.7, 2.6] })
    },
    {
      key: 'mid',
      colorClass: 'text-forest-900',
      opacity: 0.55,
      blur: 3.5,
      items: cluster(4, { x: [880, 1720], y: [-70, 70], rotate: [148, 212], scale: [1.1, 1.9] })
    },
    {
      key: 'near',
      colorClass: 'text-forest-950',
      opacity: 0.9,
      blur: 0,
      items: [
        // Тяжёлая масса в правом верхнем углу: она обрамляет свет
        // и не даёт ему растечься по всей ширине.
        ...cluster(3, { x: [1240, 1760], y: [-90, 20], rotate: [154, 202], scale: [2.0, 3.0] }),
        ...cluster(1, { x: [900, 1120], y: [-70, 10], rotate: [162, 196], scale: [1.4, 1.9] })
      ]
    }
  ]
})

/**
 * Лучи из точки света. Они делают две работы разом: объясняют, откуда
 * свет, и заполняют пустую левую половину, не добавляя туда предметов,
 * которые спорили бы с именем.
 */
const rays = computed(() => {
  const random = makeRandom((props.seed || 1) + 991)

  return [
    { angle: 14, width: 26, length: 900, opacity: 0.1 },
    { angle: 29, width: 52, length: 980, opacity: 0.14 },
    { angle: 45, width: 34, length: 1050, opacity: 0.1 },
    { angle: 61, width: 68, length: 1100, opacity: 0.08 },
    { angle: 76, width: 30, length: 1150, opacity: 0.06 }
  ].map((ray, index) => ({
    ...ray,
    key: index,
    angle: round(ray.angle + (random() - 0.5) * 9)
  }))
})
</script>

<template>
  <svg
    viewBox="0 0 1600 560"
    preserveAspectRatio="xMidYMid slice"
    aria-hidden="true"
    focusable="false"
  >
    <defs>
      <linearGradient
        :id="`sky-${uid}`"
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

      <!-- Свет, пробивающийся сквозь крону: держит правый верх и
                 оставляет левый низ тёмным — там текст. -->
      <radialGradient
        :id="`bloom-${uid}`"
        cx="0.72"
        cy="0.18"
        r="0.6"
      >
        <stop
          offset="0"
          stop-color="#c8d9bd"
          stop-opacity="0.62"
        />
        <stop
          offset="0.38"
          stop-color="#7ba068"
          stop-opacity="0.26"
        />
        <stop
          offset="1"
          stop-color="#5a8148"
          stop-opacity="0"
        />
      </radialGradient>

      <radialGradient
        :id="`sun-${uid}`"
        cx="0.735"
        cy="0.13"
        r="0.19"
      >
        <stop
          offset="0"
          stop-color="#f6f1e6"
          stop-opacity="0.4"
        />
        <stop
          offset="0.6"
          stop-color="#f6f1e6"
          stop-opacity="0.08"
        />
        <stop
          offset="1"
          stop-color="#f6f1e6"
          stop-opacity="0"
        />
      </radialGradient>

      <linearGradient
        :id="`ray-${uid}`"
        x1="0"
        y1="0"
        x2="0"
        y2="1"
      >
        <stop
          offset="0"
          stop-color="#e4ecdd"
          stop-opacity="0.95"
        />
        <stop
          offset="0.5"
          stop-color="#c8d9bd"
          stop-opacity="0.3"
        />
        <stop
          offset="1"
          stop-color="#a9c49b"
          stop-opacity="0"
        />
      </linearGradient>

      <filter
        :id="`ray-blur-${uid}`"
        x="-60%"
        y="-30%"
        width="220%"
        height="160%"
      >
        <feGaussianBlur stdDeviation="17" />
      </filter>

      <filter
        :id="`grain-${uid}`"
        x="0"
        y="0"
        width="100%"
        height="100%"
      >
        <feTurbulence
          type="fractalNoise"
          baseFrequency="0.9"
          numOctaves="3"
          stitchTiles="stitch"
        />
        <feColorMatrix
          type="saturate"
          values="0"
        />
      </filter>

      <filter
        v-for="layer in layers"
        :id="`blur-${layer.key}-${uid}`"
        :key="`f-${layer.key}`"
        x="-20%"
        y="-20%"
        width="140%"
        height="140%"
      >
        <feGaussianBlur :stdDeviation="layer.blur" />
      </filter>

      <g
        v-for="(frond, index) in fronds"
        :id="`frond-${index}-${uid}`"
        :key="index"
      >
        <path
          :d="frond.stem"
          fill="none"
          stroke="currentColor"
          stroke-width="3"
          stroke-linecap="round"
        />
        <path
          v-for="(leaf, leafIndex) in frond.leaves"
          :key="leafIndex"
          :d="leaf"
          fill="currentColor"
        />
      </g>
    </defs>

    <rect
      width="1600"
      height="560"
      :fill="`url(#sky-${uid})`"
    />
    <rect
      width="1600"
      height="560"
      :fill="`url(#bloom-${uid})`"
    />
    <rect
      width="1600"
      height="560"
      :fill="`url(#sun-${uid})`"
    />

    <!-- Лучи идут из той же точки, что и свечение, и уходят влево вниз —
             к имени, а не от него. -->
    <g
      :filter="`url(#ray-blur-${uid})`"
      style="mix-blend-mode: screen"
    >
      <g
        v-for="ray in rays"
        :key="ray.key"
        :transform="`translate(1176 73) rotate(${ray.angle})`"
        :opacity="ray.opacity"
      >
        <path
          :d="`M0 0L${-ray.width} ${ray.length}L${ray.width} ${ray.length}Z`"
          :fill="`url(#ray-${uid})`"
        />
      </g>
    </g>

    <g
      v-for="layer in layers"
      :key="layer.key"
      :class="layer.colorClass"
      :opacity="layer.opacity"
      :filter="layer.blur ? `url(#blur-${layer.key}-${uid})` : undefined"
    >
      <use
        v-for="item in layer.items"
        :key="item.key"
        :href="`#frond-${item.variant}-${uid}`"
        :transform="`translate(${item.x} ${item.y}) rotate(${item.rotate}) scale(${item.flip * item.scale} ${item.scale})`"
      />
    </g>

    <!-- Зерно: без него слои читаются как вектор из стокового набора. -->
    <rect
      width="1600"
      height="700"
      :filter="`url(#grain-${uid})`"
      opacity="0.14"
      style="mix-blend-mode: overlay"
    />
  </svg>
</template>
