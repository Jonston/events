<script setup lang="ts">
/**
 * Смысловая половина формы.
 *
 * Формы кабинета разложены на две колонки: слева одно занятие, справа
 * другое, и у каждой — свой заголовок. Простыня полей во всю ширину
 * читается как список вопросов без порядка, а пара озаглавленных колонок
 * сама говорит, что здесь заполняют вместе.
 */
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <section class="space-y-4">
    <div>
      <h2 class="font-display text-lg text-highlighted">
        {{ title }}
      </h2>
      <p
        v-if="description"
        class="mt-0.5 text-sm text-muted"
      >
        {{ description }}
      </p>
    </div>

    <div class="space-y-4">
      <slot />
    </div>
  </section>
</template>
