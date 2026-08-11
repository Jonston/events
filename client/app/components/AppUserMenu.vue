<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

const props = defineProps<{ collapsed?: boolean }>()

const colorMode = useColorMode()
const { currentUser, logout } = useAuth()

const userButtonProps = computed(() => ({
  label: props.collapsed ? undefined : (currentUser.value?.name ?? ''),
  trailingIcon: props.collapsed ? undefined : 'i-lucide-chevrons-up-down'
}))

const items = computed<DropdownMenuItem[][]>(() => [
  [
    {
      type: 'label' as const,
      label: currentUser.value?.name ?? '',
      description: currentUser.value?.email ?? ''
    }
  ],
  [
    {
      label: 'Светлая тема',
      icon: 'i-lucide-sun',
      type: 'checkbox' as const,
      checked: colorMode.value === 'light',
      onSelect(event: Event) {
        event.preventDefault()
        colorMode.preference = 'light'
      }
    },
    {
      label: 'Тёмная тема',
      icon: 'i-lucide-moon',
      type: 'checkbox' as const,
      checked: colorMode.value === 'dark',
      onSelect(event: Event) {
        event.preventDefault()
        colorMode.preference = 'dark'
      }
    },
    {
      label: 'Системная тема',
      icon: 'i-lucide-monitor',
      type: 'checkbox' as const,
      checked: colorMode.preference === 'system',
      onSelect(event: Event) {
        event.preventDefault()
        colorMode.preference = 'system'
      }
    }
  ],
  [
    {
      label: 'Выйти',
      icon: 'i-lucide-log-out',
      onSelect: () => logout()
    }
  ]
])
</script>

<template>
  <UDropdownMenu
    :items="items"
    :content="{ align: 'center', collisionPadding: 12 }"
    :ui="{ content: collapsed ? 'w-48' : 'w-(--reka-dropdown-menu-trigger-width)' }"
  >
    <UButton
      v-bind="userButtonProps"
      icon="i-lucide-user"
      color="neutral"
      variant="ghost"
      block
      :square="collapsed"
      class="data-[state=open]:bg-elevated"
      :ui="{ trailingIcon: 'text-dimmed' }"
    />
  </UDropdownMenu>
</template>
