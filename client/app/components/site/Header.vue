<script setup lang="ts">
import type { DropdownMenuItem } from '@nuxt/ui'

const { currentUser, hasRole, logout } = useAuth()
const route = useRoute()
const appName = useRuntimeConfig().public.siteName as string

const navLinks = [
  { label: 'Главная', to: '/' },
  { label: 'Мастера', to: '/masters' },
  { label: 'Мероприятия', to: '/events' }
]

const mobileOpen = ref(false)

// Меню закрывается само при переходе: иначе после клика по ссылке человек
// смотрит на новую страницу сквозь открытую панель.
watch(() => route.fullPath, () => {
  mobileOpen.value = false
})

function isActive(to: string): boolean {
  if (to === '/') return route.path === '/'
  return route.path.startsWith(to)
}

/**
 * Буквы имени, когда снимка нет: две, заглавными. Разбор имени внутри
 * аватара отдаёт их строчными, и в шапке рядом с прописными это читалось
 * как опечатка.
 */
const initials = computed(() => (currentUser.value?.name ?? '')
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map(word => word[0]!.toUpperCase())
  .join(''))

/**
 * Меню читается тремя блоками: кто я, куда я хожу, и выход.
 * Разделители несут именно это, поэтому их ровно два, а не по одному
 * между пунктами.
 */
const userMenu = computed<DropdownMenuItem[][]>(() => [
  [
    {
      type: 'label' as const,
      label: currentUser.value?.name ?? '',
      description: currentUser.value?.email ?? ''
    }
  ],
  [
    // Рабочие кабинеты показываем те, что человеку доступны: у мастера
    // их два — свой и участника, и уводить его из шапки всегда в один
    // значило бы прятать второй.
    ...(hasRole('admin')
      ? [{ label: 'Админка', icon: 'i-lucide-shield-check', to: '/admin' }]
      : []),
    ...(hasRole('master')
      ? [{ label: 'Кабинет мастера', icon: 'i-lucide-sparkles', to: '/master' }]
      : []),
    { label: 'Мой кабинет', icon: 'i-lucide-user', to: '/cabinet' }
  ],
  [
    { label: 'Выйти', icon: 'i-lucide-log-out', onSelect: () => logout() }
  ]
])
</script>

<template>
  <!-- ── Шапка ──
       Остаётся на экране при прокрутке, и обёртки вокруг неё нет намеренно:
       `sticky` двигается только в пределах своего родителя, а внутри блока
       высотой в саму шапку она уезжала бы вместе с ним. -->
  <header class="sticky top-0 z-50 border-b border-forest-100 bg-paper/95 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-page items-center justify-between px-6">
      <NuxtLink
        to="/"
        class="flex items-center gap-2.5"
      >
        <AppLogo class="size-6 text-forest-600" />
        <span class="font-display text-2xl font-medium tracking-wide text-forest-900">
          {{ appName }}
        </span>
      </NuxtLink>

      <nav class="hidden items-center gap-7 md:flex">
        <NuxtLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="border-b pb-0.5 text-base tracking-wide transition-colors duration-200"
          :class="isActive(link.to)
            ? 'border-forest-600 text-forest-800'
            : 'border-transparent text-stone-600 hover:text-forest-800'"
        >
          {{ link.label }}
        </NuxtLink>
      </nav>

      <div class="flex items-center gap-3">
        <UDropdownMenu
          v-if="currentUser"
          :items="userMenu"
          :content="{ align: 'end', collisionPadding: 12 }"
          class="hidden md:block"
        >
          <!-- Только снимок: имя человек и так знает, а в шапке оно занимало
               столько же места, сколько целый пункт меню. -->
          <button
            type="button"
            class="rounded-full ring-forest-200 transition hover:ring-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-forest-500 data-[state=open]:ring-2"
            :aria-label="`Меню: ${currentUser.name}`"
          >
            <UAvatar
              :src="currentUser.avatarUrl ?? undefined"
              :alt="currentUser.name"
              :text="initials"
              size="lg"
              :ui="{
                // Кегль букв задаётся на корне: у аватара он идёт от размера,
                // и на вложенном элементе его нечем перебить.
                root: 'bg-forest-100 text-xs',
                fallback: 'font-medium tracking-wide text-forest-800'
              }"
            />
          </button>
        </UDropdownMenu>

        <template v-else>
          <NuxtLink
            to="/auth/login"
            class="hidden text-sm text-stone-600 transition-colors hover:text-forest-800 md:inline"
          >
            Войти
          </NuxtLink>
          <UButton
            to="/events"
            color="secondary"
            size="lg"
            trailing-icon="i-lucide-arrow-right"
            class="hidden rounded-full md:inline-flex"
          >
            Записаться
          </UButton>
        </template>

        <UButton
          color="neutral"
          variant="ghost"
          class="md:hidden"
          :icon="mobileOpen ? 'i-lucide-x' : 'i-lucide-menu'"
          :aria-expanded="mobileOpen"
          aria-controls="mobile-nav"
          aria-label="Меню"
          @click="mobileOpen = !mobileOpen"
        />
      </div>
    </div>
  </header>

  <!-- ── Мобильное меню ── -->
  <Transition name="slide-down">
    <div
      v-if="mobileOpen"
      id="mobile-nav"
      class="z-40 border-b border-forest-100 bg-white px-6 py-4 shadow-lg md:hidden"
    >
      <nav class="space-y-0.5">
        <NuxtLink
          v-for="link in navLinks"
          :key="link.to"
          :to="link.to"
          class="block rounded-xl px-4 py-2.5 text-base transition-colors"
          :class="isActive(link.to)
            ? 'bg-forest-50 text-forest-800'
            : 'text-stone-600 hover:bg-forest-50/60 hover:text-forest-800'"
        >
          {{ link.label }}
        </NuxtLink>
      </nav>

      <div class="mt-3 space-y-2 border-t border-forest-100 pt-3">
        <template v-if="currentUser">
          <p class="px-4 pb-1 text-sm text-stone-500">
            {{ currentUser.name }}
          </p>
          <NuxtLink
            v-if="hasRole('admin')"
            to="/admin"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-base text-stone-600 transition-colors hover:bg-forest-50 hover:text-forest-800"
          >
            <UIcon
              name="i-lucide-shield-check"
              class="size-4.5 shrink-0 text-forest-500"
            />
            Админка
          </NuxtLink>
          <NuxtLink
            v-if="hasRole('master')"
            to="/master"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-base text-stone-600 transition-colors hover:bg-forest-50 hover:text-forest-800"
          >
            <UIcon
              name="i-lucide-sparkles"
              class="size-4.5 shrink-0 text-forest-500"
            />
            Кабинет мастера
          </NuxtLink>
          <NuxtLink
            to="/cabinet"
            class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-base text-stone-600 transition-colors hover:bg-forest-50 hover:text-forest-800"
          >
            <UIcon
              name="i-lucide-user"
              class="size-4.5 shrink-0 text-forest-500"
            />
            Мой кабинет
          </NuxtLink>
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-left text-base text-stone-600 transition-colors hover:bg-forest-50 hover:text-forest-800"
            @click="logout()"
          >
            <UIcon
              name="i-lucide-log-out"
              class="size-4.5 shrink-0 text-forest-500"
            />
            Выйти
          </button>
        </template>

        <NuxtLink
          v-else
          to="/auth/login"
          class="block rounded-xl px-4 py-2.5 text-base text-stone-600 transition-colors hover:bg-forest-50 hover:text-forest-800"
        >
          Войти
        </NuxtLink>

        <UButton
          to="/events"
          color="secondary"
          size="lg"
          block
          trailing-icon="i-lucide-arrow-right"
          class="rounded-full"
        >
          Записаться
        </UButton>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
