<script setup lang="ts">
/**
 * Декоративная ветвь. Держит те места, где в референсах стоит фотография,
 * а у нас снимка ещё нет: пустая карточка выглядит недоделкой, ветвь на
 * тонированном фоне — намеренным приёмом. Когда фото загрузят, компонент
 * просто уступает место.
 */
defineProps({
  // Плотность рисунка: на крупных плоскостях уместнее развёрнутая ветвь.
  variant: { type: String, default: 'branch' }
})
</script>

<template>
  <svg
    viewBox="0 0 200 200"
    fill="none"
    stroke="currentColor"
    stroke-width="1.1"
    stroke-linecap="round"
    aria-hidden="true"
    focusable="false"
  >
    <g v-if="variant === 'branch'">
      <path d="M100 190C100 130 100 80 100 20" />
      <g
        v-for="(y, i) in [46, 74, 102, 130, 158]"
        :key="y"
      >
        <path
          :d="`M100 ${y}c-16-4-27-14-31-29 17-2 28 6 31 29z`"
          :opacity="0.9 - i * 0.11"
        />
        <path
          :d="`M100 ${y + 14}c16-4 27-14 31-29-17-2-28 6-31 29z`"
          :opacity="0.82 - i * 0.11"
        />
      </g>
    </g>

    <g v-else>
      <circle
        cx="100"
        cy="100"
        r="62"
        opacity="0.35"
      />
      <circle
        cx="100"
        cy="100"
        r="44"
        opacity="0.55"
      />
      <path
        d="M100 144c-22-6-36-22-40-44 24-3 39 9 40 44z"
        opacity="0.9"
      />
      <path
        d="M100 144c22-6 36-22 40-44-24-3-39 9-40 44z"
        opacity="0.7"
      />
      <path
        d="M100 144v22"
        opacity="0.9"
      />
    </g>
  </svg>
</template>
