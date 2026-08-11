<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Мероприятие'
})

const route = useRoute()
const id = computed(() => Number(route.params.id))

const { fetchEvent } = useMasterApi()
const { data: event } = await useAsyncData(
  () => `master-event-${id.value}`,
  () => fetchEvent(id.value)
)

if (!event.value) {
  throw createError({ statusCode: 404, statusMessage: 'Мероприятие не найдено', fatal: true })
}
</script>

<template>
  <MasterEventForm :event="event" />
</template>
