<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { MasterLead } from '~/types'

const props = defineProps<{
  leads: MasterLead[]
  pending: boolean
  sort: string
  direction: 'asc' | 'desc'
}>()

const emit = defineEmits<{
  sort: [column: string]
  open: [lead: MasterLead]
}>()

/** Цвет статуса: бронь — то, ради чего всё, спам — то, что не жалко. */
const statusColor: Record<string, 'primary' | 'success' | 'warning' | 'neutral'> = {
  new: 'primary',
  booked: 'success',
  refunded: 'warning',
  spam: 'neutral'
}

function sortIcon(column: string): string {
  if (props.sort !== column) return 'i-lucide-chevrons-up-down'

  return props.direction === 'asc' ? 'i-lucide-arrow-up' : 'i-lucide-arrow-down'
}

const columns: TableColumn<MasterLead>[] = [
  { accessorKey: 'name', header: 'Кто' },
  { accessorKey: 'eventTitle', header: 'Куда' },
  { accessorKey: 'message', header: 'Сообщение' },
  { accessorKey: 'status', header: 'Статус' },
  { accessorKey: 'createdAt', header: 'Пришла' },
  // Заголовок непустой намеренно: пустая строка не даёт серверу ни одного
  // узла, а на клиенте Vue создаёт под неё якоря фрагмента — разметка
  // расходится, и таблица перерисовывается заново после гидрации.
  { id: 'actions', header: 'Действия' }
]
</script>

<template>
  <UTable
    :data="leads"
    :columns="columns"
    :loading="pending"
  >
    <template #name-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="Кто"
        :trailing-icon="sortIcon('name')"
        @click="emit('sort', 'name')"
      />
    </template>

    <template #status-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="Статус"
        :trailing-icon="sortIcon('status')"
        @click="emit('sort', 'status')"
      />
    </template>

    <template #createdAt-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="Пришла"
        :trailing-icon="sortIcon('created_at')"
        @click="emit('sort', 'created_at')"
      />
    </template>

    <template #actions-header>
      <span class="sr-only">Действия</span>
    </template>

    <template #name-cell="{ row }">
      <div class="min-w-0">
        <p class="flex items-center gap-2 text-highlighted">
          {{ row.original.name }}
          <UIcon
            v-if="row.original.fromMember"
            name="i-lucide-user-check"
            class="size-4 shrink-0 text-primary"
            title="Оставил вошедший участник"
          />
        </p>
        <p class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm">
          <a
            v-if="row.original.phone"
            :href="`tel:${row.original.phone}`"
            class="text-primary hover:underline"
          >{{ row.original.phone }}</a>
          <a
            v-if="row.original.email"
            :href="`mailto:${row.original.email}`"
            class="text-muted hover:underline"
          >{{ row.original.email }}</a>
        </p>
      </div>
    </template>

    <template #eventTitle-cell="{ row }">
      <NuxtLink
        v-if="row.original.eventSlug"
        :to="`/events/${row.original.eventSlug}`"
        target="_blank"
        class="text-primary hover:underline"
      >
        {{ row.original.eventTitle }}
      </NuxtLink>
      <span
        v-else
        class="text-dimmed"
      >Со страницы мастера</span>
    </template>

    <template #message-cell="{ row }">
      <p class="line-clamp-2 max-w-72 text-muted">
        {{ row.original.message ?? '—' }}
      </p>
    </template>

    <template #status-cell="{ row }">
      <UBadge
        :color="statusColor[row.original.status] ?? 'neutral'"
        variant="subtle"
        size="lg"
      >
        {{ row.original.statusLabel }}
      </UBadge>
    </template>

    <template #createdAt-cell="{ row }">
      <span class="whitespace-nowrap text-muted">{{ formatDate(row.original.createdAt, true) }}</span>
    </template>

    <template #actions-cell="{ row }">
      <UButton
        color="neutral"
        variant="outline"
        size="sm"
        icon="i-lucide-pencil-line"
        :aria-label="`Изменить статус заявки от ${row.original.name}`"
        @click="emit('open', row.original)"
      >
        Статус
      </UButton>
    </template>

    <template #empty>
      <div class="py-12 text-center text-muted">
        Заявок не нашлось.
      </div>
    </template>
  </UTable>
</template>
