<script setup lang="ts">
const route = useRoute()

/*
 * Полосу-призыв прячем там, где она мешает: на страницах мероприятий форма
 * заявки уже на экране, а на входе и в кабинете человек пришёл с конкретным
 * делом, и «выберите мероприятие» его только уводит.
 */
const hideCta = computed(() =>
  ['/events', '/auth', '/admin'].some(prefix => route.path.startsWith(prefix))
)
</script>

<template>
  <div class="flex min-h-screen flex-col bg-paper font-body text-stone-700">
    <SiteHeader />

    <main class="flex-1">
      <slot />
    </main>

    <SiteCtaBand v-if="!hideCta" />
    <SiteFooter />
  </div>
</template>
