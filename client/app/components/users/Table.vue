<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'
import type { User, UserRole } from '~/types'

const props = defineProps<{
  users: User[]
  pending: boolean
  sort: string
  direction: 'asc' | 'desc'
}>()

const emit = defineEmits<{ sort: [column: string] }>()

const roleLabel: Record<UserRole, string> = {
  admin: 'Администратор',
  master: 'Мастер',
  member: 'Участник'
}

const roleColor: Record<UserRole, 'primary' | 'secondary' | 'neutral'> = {
  admin: 'primary',
  master: 'secondary',
  member: 'neutral'
}

/** Стрелка в заголовке: показывает, по какой колонке идёт сортировка. */
function sortIcon(column: string): string {
  if (props.sort !== column) return 'i-lucide-chevrons-up-down'
  return props.direction === 'asc' ? 'i-lucide-arrow-up' : 'i-lucide-arrow-down'
}

const columns: TableColumn<User>[] = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'name', header: 'Имя' },
  { accessorKey: 'email', header: 'Email' },
  { accessorKey: 'phone', header: 'Телефон' },
  { accessorKey: 'roles', header: 'Роли' },
  { accessorKey: 'createdAt', header: 'Регистрация' }
]
</script>

<template>
  <UTable
    :data="users"
    :columns="columns"
    :loading="pending"
  >
    <template #id-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="ID"
        :trailing-icon="sortIcon('id')"
        @click="emit('sort', 'id')"
      />
    </template>

    <template #name-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="Имя"
        :trailing-icon="sortIcon('name')"
        @click="emit('sort', 'name')"
      />
    </template>

    <template #email-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="Email"
        :trailing-icon="sortIcon('email')"
        @click="emit('sort', 'email')"
      />
    </template>

    <template #createdAt-header>
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        label="Регистрация"
        :trailing-icon="sortIcon('created_at')"
        @click="emit('sort', 'created_at')"
      />
    </template>

    <template #id-cell="{ row }">
      <span class="font-mono text-muted">#{{ row.original.id }}</span>
    </template>

    <template #name-cell="{ row }">
      <div class="flex items-center gap-3">
        <UAvatar
          :src="row.original.avatarUrl ?? undefined"
          :alt="row.original.name"
          size="md"
        />
        <div class="min-w-0">
          <p class="truncate text-highlighted">
            {{ row.original.name }}
          </p>
          <p
            v-if="!row.original.emailVerified"
            class="text-xs text-warning"
          >
            Email не подтверждён
          </p>
        </div>
      </div>
    </template>

    <template #email-cell="{ row }">
      <span>{{ row.original.email }}</span>
    </template>

    <template #phone-cell="{ row }">
      <span class="text-muted">{{ row.original.phone ?? '—' }}</span>
    </template>

    <template #roles-cell="{ row }">
      <div class="flex flex-wrap gap-1">
        <UBadge
          v-for="role in row.original.roles"
          :key="role"
          :color="roleColor[role] ?? 'neutral'"
          variant="subtle"
          size="lg"
        >
          {{ roleLabel[role] ?? role }}
        </UBadge>
        <span
          v-if="row.original.roles.length === 0"
          class="text-dimmed"
        >—</span>
      </div>
    </template>

    <template #createdAt-cell="{ row }">
      <span class="text-muted">{{ formatDate(row.original.createdAt, true) }}</span>
    </template>

    <template #empty>
      <div class="py-12 text-center text-muted">
        Пользователи не найдены.
      </div>
    </template>
  </UTable>
</template>
