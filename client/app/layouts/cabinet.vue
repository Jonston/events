<script setup lang="ts">
const { home, brand, navItems, searchGroups } = useCabinetNav()

const route = useRoute()
const pageTitle = computed(() => String(route.meta.pageTitle ?? ''))

/**
 * Куда возвращает стрелка слева от заголовка. Есть только у экранов,
 * в которые заходят из списка: на разделе меню ей некуда вести.
 */
const backTo = computed(() => route.meta.backTo as string | undefined)
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
          <template
            v-if="backTo"
            #leading
          >
            <UButton
              :to="backTo"
              color="neutral"
              variant="ghost"
              icon="i-lucide-arrow-left"
              aria-label="Назад"
              class="-ms-1.5"
            />
          </template>

          <!-- Кнопки экрана приезжают сюда телепортом: они принадлежат
               форме и живут её состоянием, а стоять должны в верхней
               панели, где их ищут. -->
          <template #right>
            <div
              id="cabinet-actions"
              class="flex items-center gap-3"
            />
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
