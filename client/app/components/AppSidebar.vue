<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

// Шапка сайдбара подписана ролью, а не названием площадки: под одним
// адресом живут три разных кабинета, и человек должен видеть, в каком он.
defineProps<{
  home: string
  brand: { label: string, icon: string }
  navItems: NavigationMenuItem[]
}>()
</script>

<template>
  <UDashboardSidebar
    resizable
    collapsible
    :min-size="14"
    :default-size="20"
    :max-size="30"
    :ui="{ footer: 'lg:border-t lg:border-default' }"
  >
    <template #header="{ collapsed }">
      <UButton
        :to="home"
        color="neutral"
        variant="ghost"
        block
        :square="collapsed"
        :leading-icon="brand.icon"
        :label="collapsed ? undefined : brand.label"
        class="data-[state=open]:bg-elevated py-2"
        :ui="{ leadingIcon: 'text-primary size-5' }"
      />
    </template>

    <template #default="{ collapsed }">
      <UDashboardSearchButton
        :collapsed="collapsed"
        class="bg-transparent ring-default"
      />

      <UNavigationMenu
        :collapsed="collapsed"
        :items="navItems"
        orientation="vertical"
        tooltip
        class="w-full px-2"
      />
    </template>

    <template #footer="{ collapsed }">
      <AppUserMenu :collapsed="collapsed" />
    </template>
  </UDashboardSidebar>
</template>
