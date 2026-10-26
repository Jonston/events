<script setup lang="ts">
const appName = useRuntimeConfig().public.siteName as string

const navLinks = [
  { label: 'Главная', to: '/' },
  { label: 'Мастера', to: '/masters' },
  { label: 'Мероприятия', to: '/events' }
]

const formats = ['Практики и занятия', 'Консультации', 'Ретриты и программы']
const socials = ['Instagram', 'Telegram', 'YouTube']
</script>

<template>
  <footer class="on-dark relative overflow-hidden bg-forest-900 text-forest-200">
    <BotanicalMark
      class="pointer-events-none absolute -left-20 bottom-0 h-72 w-72 text-forest-700/50"
      variant="branch"
    />

    <div class="relative mx-auto max-w-page px-6 pb-8 pt-12">
      <div class="grid gap-8 pb-10 sm:grid-cols-2 lg:grid-cols-4">
        <div class="space-y-4">
          <div class="flex items-center gap-2.5">
            <AppLogo class="size-6 text-forest-400" />
            <span class="font-display text-2xl text-white">{{ appName }}</span>
          </div>
          <p class="max-w-xs leading-relaxed">
            Площадка мастеров: практики, консультации и выездные программы.
            Ближайшие даты, честные цены и понятные условия участия.
          </p>
          <div class="flex gap-2">
            <a
              v-for="social in socials"
              :key="social"
              href="#"
              :aria-label="social"
              class="flex size-9 items-center justify-center rounded-full border border-forest-700 text-xs transition-colors duration-200 hover:border-forest-400 hover:text-white"
            >
              {{ social.slice(0, 2) }}
            </a>
          </div>
        </div>

        <div>
          <h5 class="mb-4 font-medium text-white">
            Навигация
          </h5>
          <ul class="space-y-2.5">
            <li
              v-for="link in navLinks"
              :key="link.to"
            >
              <NuxtLink
                :to="link.to"
                class="transition-colors duration-200 hover:text-white"
              >
                {{ link.label }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <div>
          <h5 class="mb-4 font-medium text-white">
            Форматы
          </h5>
          <ul class="space-y-2.5">
            <li
              v-for="format in formats"
              :key="format"
            >
              <NuxtLink
                to="/events"
                class="transition-colors duration-200 hover:text-white"
              >
                {{ format }}
              </NuxtLink>
            </li>
          </ul>
        </div>

        <div>
          <h5 class="mb-4 font-medium text-white">
            Связь
          </h5>
          <ul class="space-y-2.5">
            <li>
              <a
                href="tel:+380671234567"
                class="flex items-center gap-2.5 transition-colors duration-200 hover:text-white"
              >
                <UIcon
                  name="i-lucide-phone"
                  class="size-4 shrink-0 text-forest-400"
                />
                +380 67 123-45-67
              </a>
            </li>
            <li>
              <a
                href="mailto:hello@example.com"
                class="flex items-center gap-2.5 transition-colors duration-200 hover:text-white"
              >
                <UIcon
                  name="i-lucide-mail"
                  class="size-4 shrink-0 text-forest-400"
                />
                hello@example.com
              </a>
            </li>
            <li class="flex items-center gap-2.5">
              <UIcon
                name="i-lucide-clock"
                class="size-4 shrink-0 text-forest-400"
              />
              Пн–Сб, 10:00 – 20:00
            </li>
          </ul>
        </div>
      </div>

      <div class="flex flex-col items-center justify-between gap-3 border-t border-forest-800 pt-6 text-sm sm:flex-row">
        <p>© 2026 {{ appName }}. Все права защищены.</p>
        <p class="text-forest-300">
          Практики, консультации и ретриты
        </p>
      </div>
    </div>
  </footer>
</template>
