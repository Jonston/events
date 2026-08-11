<script setup lang="ts">
definePageMeta({
  middleware: 'cabinet-guest',
  layout: 'auth'
})

const { login, refreshIdentity, currentUser } = useAuth()
const route = useRoute()
const config = useRuntimeConfig().public
const appName = config.siteName as string

const credentials = reactive({
  email: '',
  password: ''
})

const loading = ref(false)
const error = ref<string | null>(null)

/**
 * Демо-аккаунты для разработки: выбор подставляет логин и пароль, чтобы
 * не набирать их руками при каждой смене роли.
 *
 * Показываются только когда это включено явно — по умолчанию в разработке.
 * На боевой площадке список готовых учёток на экране входа означал бы,
 * что зайти под администратором может кто угодно.
 */
const demoAccounts = [
  { label: 'Администратор — admin@example.com', value: 'admin@example.com' },
  { label: 'Мастер — master@example.com', value: 'master@example.com' },
  { label: 'Участник — user@example.com', value: 'user@example.com' }
]

const showDemoAccounts = computed(() => Boolean(config.demoLogin))
const selectedDemo = ref<string | undefined>(undefined)

watch(selectedDemo, (email) => {
  if (!email) return

  credentials.email = email
  credentials.password = 'password'
})

async function handleLogin() {
  loading.value = true
  error.value = null

  try {
    await login(credentials)

    // Модульный редирект отключён: кабинет у ролей разный, и куда идти,
    // известно только после того, как стало известно, кто вошёл.
    await refreshIdentity()

    // Человека, которого развернули с закрытой страницы, возвращаем именно
    // туда — но только внутрь площадки: адрес приходит из ссылки, и уводить
    // по нему на чужой домен нельзя.
    const requested = String(route.query.redirect ?? '')
    const target = requested.startsWith('/') && !requested.startsWith('//')
      ? requested
      : cabinetHome(currentUser.value)

    await navigateTo(target)
  } catch (err: unknown) {
    const failure = err as { data?: { message?: string }, message?: string }
    error.value = failure?.data?.message ?? failure?.message ?? 'Неверный email или пароль'
    loading.value = false
  }
}
</script>

<template>
  <UCard>
    <template #header>
      <div class="text-center">
        <UIcon
          name="i-lucide-sprout"
          class="size-8 text-primary"
        />
        <h1 class="mt-2 font-display text-2xl font-bold text-highlighted">
          {{ appName }}
        </h1>
        <p class="text-muted mt-1">
          Войдите в свой аккаунт
        </p>
      </div>
    </template>

    <!--
      `onsubmit` статическим атрибутом, а не только обработчиком Vue.

      Обработчик появляется после гидрации, а до неё браузер отправляет форму
      сам — GET на текущий адрес, и пароль оказывается в адресной строке,
      в истории и в логах сервера. Атрибут уходит в серверную разметку
      и запрещает это с первой же отрисовки.
    -->
    <form
      class="space-y-4"
      onsubmit="return false"
      @submit.prevent="handleLogin"
    >
      <UAlert
        v-if="error"
        color="error"
        variant="subtle"
        :description="error"
        icon="i-lucide-circle-alert"
      />

      <UFormField
        v-if="showDemoAccounts"
        label="Войти как"
        name="demo"
        description="Учётные записи для разработки — подставляют логин и пароль."
      >
        <USelect
          v-model="selectedDemo"
          :items="demoAccounts"
          placeholder="Выберите роль"
          size="lg"
          class="w-full"
        />
      </UFormField>

      <UFormField
        label="Email"
        name="email"
      >
        <UInput
          v-model="credentials.email"
          type="email"
          placeholder="admin@example.com"
          size="lg"
          class="w-full"
          autocomplete="email"
          required
        />
      </UFormField>

      <UFormField
        label="Пароль"
        name="password"
      >
        <UInput
          v-model="credentials.password"
          type="password"
          placeholder="••••••••"
          size="lg"
          class="w-full"
          autocomplete="current-password"
          required
        />
      </UFormField>

      <UButton
        type="submit"
        block
        size="lg"
        :loading="loading"
      >
        Войти
      </UButton>
    </form>
  </UCard>
</template>
