<script setup lang="ts">
/**
 * Звёзды рейтинга — для показа и для выбора оценки.
 *
 * Дробная часть рисуется обрезкой заливки, а не округлением до половины:
 * 4.2 и 4.4 — разные оценки, и округление обеих до «четыре с половиной»
 * означало бы показывать рейтинг, которого нет.
 */

const props = defineProps({
  // Показываемое значение, 0…5. В режиме выбора не используется.
  value: { type: Number, default: 0 },
  size: { type: Number, default: 18 },
  // true — звёзды становятся полем ввода оценки.
  interactive: { type: Boolean, default: false },
  label: { type: String, default: 'Оценка' }
})

const model = defineModel({ type: Number, default: 0 })

const uid = useId()
const hovered = ref(0)

const stars = [1, 2, 3, 4, 5]

const shown = computed(() => {
  if (!props.interactive) return props.value

  return hovered.value || model.value
})

const STAR = 'M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.4-5.8-3-5.8 3 1.1-6.4L2.6 9.4l6.5-.9z'

/** Доля заливки конкретной звезды: 1 — целая, 0.35 — чуть больше трети. */
function fill(star: number): number {
  return Math.min(Math.max(shown.value - star + 1, 0), 1)
}

function choose(star: number): void {
  model.value = star
}
</script>

<template>
  <!--
        Корень — span, а не div: рейтинг стоит внутри абзацев и строк текста,
        а блочный тег внутри <p> браузер по правилам разбора выносит наружу.
        Серверная разметка тогда расходится с клиентской, и Vue при гидрации
        перерисовывает кусок страницы заново.
    -->
  <span
    :class="interactive ? 'inline-flex items-center gap-1' : 'inline-flex items-center gap-0.5'"
    :role="interactive ? 'radiogroup' : 'img'"
    :aria-label="interactive ? label : `${label}: ${value.toFixed(1)} из 5`"
  >
    <component
      :is="interactive ? 'button' : 'span'"
      v-for="star in stars"
      :key="star"
      :type="interactive ? 'button' : undefined"
      :role="interactive ? 'radio' : undefined"
      :aria-checked="interactive ? model === star : undefined"
      :aria-label="interactive ? `${star} из 5` : undefined"
      :class="interactive ? 'rounded-md p-0.5 transition-transform hover:scale-110' : 'block'"
      @click="interactive && choose(star)"
      @mouseenter="interactive && (hovered = star)"
      @mouseleave="interactive && (hovered = 0)"
    >
      <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        aria-hidden="true"
        focusable="false"
      >
        <defs>
          <linearGradient
            :id="`star-${uid}-${star}`"
            x1="0"
            y1="0"
            x2="1"
            y2="0"
          >
            <stop
              :offset="fill(star)"
              stop-color="currentColor"
            />
            <stop
              :offset="fill(star)"
              stop-color="transparent"
            />
          </linearGradient>
        </defs>
        <!-- Пустая звезда остаётся контуром, а не серой заливкой:
                     заливка другого цвета читается как ещё одна оценка. -->
        <path
          :d="STAR"
          fill="none"
          stroke="currentColor"
          stroke-width="1.4"
          stroke-linejoin="round"
          opacity="0.45"
        />
        <path
          :d="STAR"
          :fill="`url(#star-${uid}-${star})`"
        />
      </svg>
    </component>
  </span>
</template>
