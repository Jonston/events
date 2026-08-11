<script setup lang="ts">
import type { MasterEvent } from '~/types'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Мои мероприятия'
})

const { fetchEvents, deleteEvent } = useMasterApi()
const toast = useToast()

const { data, status, refresh } = await useAsyncData('master-events', fetchEvents)

const events = computed<MasterEvent[]>(() => data.value?.data ?? [])
const pending = computed(() => status.value === 'pending')

/**
 * Выбранный вид переживает перезагрузку: человек, который ведёт расписание
 * календарём, не должен каждый раз переключаться на него заново.
 */
const view = useCookie<'calendar' | 'table'>('master-events-view', { default: () => 'table' })

const viewItems = [
  { value: 'table', label: 'Списком', icon: 'i-lucide-list' },
  { value: 'calendar', label: 'Календарём', icon: 'i-lucide-calendar-days' }
]

const pendingDelete = ref<MasterEvent | null>(null)
const deleting = ref(false)

async function confirmDelete() {
  if (!pendingDelete.value) return

  deleting.value = true

  try {
    await deleteEvent(pendingDelete.value.id)
    toast.add({ title: 'Мероприятие удалено', color: 'success' })
    pendingDelete.value = null
    await refresh()
  } catch {
    toast.add({ title: 'Не удалось удалить', color: 'error' })
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
      <UTabs
        v-model="view"
        :items="viewItems"
        :content="false"
        size="sm"
        class="w-auto"
      />

      <UButton
        to="/master/events/create"
        color="secondary"
        size="lg"
        icon="i-lucide-plus"
        class="rounded-full"
      >
        Новое мероприятие
      </UButton>
    </div>

    <div
      v-if="pending"
      class="flex justify-center py-12"
    >
      <UIcon
        name="i-lucide-loader-circle"
        class="animate-spin text-3xl text-dimmed"
      />
    </div>

    <div
      v-else-if="!events.length"
      class="rounded-xl border border-dashed border-default px-6 py-14 text-center"
    >
      <BotanicalMark
        class="mx-auto size-20 text-forest-300"
        variant="seal"
      />
      <p class="mt-4 font-medium text-highlighted">
        Мероприятий пока нет
      </p>
      <p class="mx-auto mt-1 max-w-sm text-sm text-muted">
        Создайте первое — оно появится на витрине сразу после публикации.
      </p>
      <UButton
        to="/master/events/create"
        color="secondary"
        size="lg"
        class="mt-5 rounded-full"
      >
        Создать мероприятие
      </UButton>
    </div>

    <template v-else>
      <!-- Календарь монтируется только на клиенте: FullCalendar считает
           размеры сетки по реальному контейнеру, которого при серверном
           рендере нет. -->
      <ClientOnly v-if="view === 'calendar'">
        <MasterEventCalendar :events="events" />
        <template #fallback>
          <div class="flex justify-center py-12">
            <UIcon
              name="i-lucide-loader-circle"
              class="animate-spin text-3xl text-dimmed"
            />
          </div>
        </template>
      </ClientOnly>

      <MasterEventTable
        v-else
        :events="events"
        @remove="event => pendingDelete = event"
      />
    </template>

    <UModal
      :open="pendingDelete !== null"
      title="Удалить мероприятие?"
      :description="pendingDelete
        ? `«${pendingDelete.title}» исчезнет с витрины. Заявки, которые по нему пришли, останутся.`
        : ''"
      @update:open="value => { if (!value) pendingDelete = null }"
    >
      <template #footer>
        <UButton
          color="neutral"
          variant="ghost"
          @click="pendingDelete = null"
        >
          Отмена
        </UButton>
        <UButton
          color="error"
          :loading="deleting"
          @click="confirmDelete"
        >
          Удалить
        </UButton>
      </template>
    </UModal>
  </div>
</template>
