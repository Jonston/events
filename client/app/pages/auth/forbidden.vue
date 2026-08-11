<script setup lang="ts">
definePageMeta({
  middleware: 'sanctum:auth',
  layout: 'auth'
})

const { currentUser, logout } = useAuth()

// Человек не «не туда попал» — у него просто другая роль. Показываем,
// куда ему можно, а не только дверь на выход.
const home = computed(() => cabinetHome(currentUser.value))
</script>

<template>
  <UCard>
    <div class="space-y-4 text-center">
      <UIcon
        name="i-lucide-shield-x"
        class="size-10 text-error"
      />

      <div>
        <h1 class="font-display text-xl font-bold text-highlighted">
          Раздел не для вашей роли
        </h1>
        <p class="text-muted mt-1">
          Учётная запись {{ currentUser?.email }} сюда не допущена.
        </p>
      </div>

      <div class="space-y-2">
        <UButton
          :to="home"
          block
          size="lg"
          icon="i-lucide-arrow-right"
        >
          В свой кабинет
        </UButton>

        <UButton
          block
          color="neutral"
          variant="subtle"
          icon="i-lucide-log-out"
          @click="logout()"
        >
          Войти под другой учётной записью
        </UButton>
      </div>
    </div>
  </UCard>
</template>
