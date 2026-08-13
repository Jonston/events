<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  backTo: '/master/events',
  pageTitle: 'Новое мероприятие'
})
</script>

<template>
  <MasterEventForm />
</template>
