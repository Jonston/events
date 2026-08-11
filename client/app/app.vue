<script setup lang="ts">
import { ru } from '@nuxt/ui/locale'
</script>

<template>
  <!--
    Локаль задаётся один раз на всё приложение: иначе служебные надписи
    самих компонентов — «Search…», подписи страниц пагинации, тексты для
    скринридеров — остаются английскими посреди русского интерфейса.
  -->
  <UApp :locale="ru">
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>
