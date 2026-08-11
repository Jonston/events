<script setup lang="ts">
const { home, brand, navItems, searchGroups } = useCabinetNav()

const route = useRoute()
const pageTitle = computed(() => String(route.meta.pageTitle ?? ''))
</script>

<template>
  <UDashboardGroup storage-key="cabinet">
    <AppSidebar
      :home="home"
      :brand="brand"
      :nav-items="navItems"
    />

    <UDashboardSearch :groups="searchGroups" />

    <UDashboardPanel id="cabinet-panel">
      <template #header>
        <UDashboardNavbar :title="pageTitle">
          <template #right>
            <slot name="actions" />
          </template>
        </UDashboardNavbar>
      </template>

      <template #body>
        <slot />
      </template>
    </UDashboardPanel>
  </UDashboardGroup>
</template>
