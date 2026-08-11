<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug))

const { fetchEvent } = useShowcaseApi()
const { data } = await useAsyncData(
  () => `event-${slug.value}`,
  () => fetchEvent(slug.value)
)

if (!data.value) {
  throw createError({ statusCode: 404, statusMessage: 'Мероприятие не найдено', fatal: true })
}

const event = computed(() => data.value!.card)
const detail = computed(() => data.value!)

useHead({ title: () => event.value.title })
</script>

<template>
  <div>
    <!-- ── Шапка мероприятия ── -->
    <div class="relative overflow-hidden border-b border-forest-100 bg-gradient-to-b from-forest-50/70 to-paper">
      <BotanicalMark
        class="pointer-events-none absolute -right-20 -top-24 hidden size-112 text-forest-200/60 lg:block"
        variant="branch"
      />

      <div class="relative mx-auto max-w-page px-6 py-11">
        <nav
          class="mb-5 flex items-center gap-2 text-sm text-stone-500"
          aria-label="Хлебные крошки"
        >
          <NuxtLink
            to="/events"
            class="transition-colors hover:text-forest-800"
          >
            Мероприятия
          </NuxtLink>
          <span aria-hidden="true">/</span>
          <span class="text-forest-800">{{ event.title }}</span>
        </nav>

        <div class="max-w-3xl">
          <h1 class="text-balance font-display text-4xl leading-tight tracking-tight text-forest-900 sm:text-5xl">
            {{ event.title }}
          </h1>
          <p
            v-if="event.summary"
            class="mt-3 text-lg leading-relaxed text-stone-600"
          >
            {{ event.summary }}
          </p>

          <NuxtLink
            v-if="event.masterSlug"
            :to="`/masters/${event.masterSlug}`"
            class="mt-5 inline-flex items-center gap-2 text-forest-700 transition-colors hover:text-coral-700"
          >
            <UIcon
              name="i-lucide-users"
              class="size-4.5"
            />
            Ведёт {{ event.masterName }}
          </NuxtLink>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-page px-6 py-11">
      <!-- Заявка занимает треть сетки и тянется вместе с контейнером,
           а не сидит на фиксированных 24rem. -->
      <div class="grid gap-9 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
        <div>
          <div
            v-if="event.image"
            class="overflow-hidden rounded-3xl"
          >
            <img
              :src="event.image"
              :alt="event.title"
              class="w-full object-cover"
            >
          </div>

          <dl
            class="grid gap-5 rounded-2xl bg-white p-5 ring-1 ring-forest-100 sm:grid-cols-2 lg:grid-cols-3"
            :class="event.image ? 'mt-6' : ''"
          >
            <div class="flex gap-3">
              <UIcon
                name="i-lucide-calendar"
                class="mt-0.5 size-5.5 shrink-0 text-forest-600"
              />
              <div>
                <dt class="text-sm text-stone-500">
                  Даты
                </dt>
                <dd class="mt-0.5 text-forest-900">
                  {{ formatRange(event.startsAt, event.endsAt) }}
                </dd>
              </div>
            </div>
            <div
              v-if="event.location"
              class="flex gap-3"
            >
              <UIcon
                name="i-lucide-map-pin"
                class="mt-0.5 size-5.5 shrink-0 text-forest-600"
              />
              <div>
                <dt class="text-sm text-stone-500">
                  Место
                </dt>
                <dd class="mt-0.5 text-forest-900">
                  {{ event.location }}
                </dd>
              </div>
            </div>
            <div
              v-if="detail.capacity"
              class="flex gap-3"
            >
              <UIcon
                name="i-lucide-users"
                class="mt-0.5 size-5.5 shrink-0 text-forest-600"
              />
              <div>
                <dt class="text-sm text-stone-500">
                  Мест в группе
                </dt>
                <dd class="mt-0.5 text-forest-900">
                  {{ detail.capacity }}
                </dd>
              </div>
            </div>
          </dl>

          <p
            v-if="detail.description"
            class="mt-8 max-w-[68ch] whitespace-pre-line text-lg leading-relaxed text-stone-700"
          >
            {{ detail.description }}
          </p>

          <!-- ── Программа по дням ──
               То, что отличает выездную программу от встречи на час.
               У кого её нет, тот блок на странице не получает. -->
          <section
            v-if="detail.schedule.length"
            class="mt-8"
          >
            <h2 class="font-display text-2xl text-forest-900">
              Программа
            </h2>

            <div
              v-for="group in detail.schedule"
              :key="group.day"
              class="mt-5"
            >
              <h3 class="font-medium text-forest-800">
                {{ formatDay(group.day) }}
                <span class="font-normal text-stone-500">· {{ formatWeekday(group.day) }}</span>
              </h3>

              <ul class="mt-2.5 border-t border-forest-100">
                <li
                  v-for="(item, index) in group.items"
                  :key="index"
                  class="flex flex-col gap-1 border-b border-forest-100 py-2.5 sm:flex-row sm:gap-4"
                >
                  <span class="shrink-0 tabular-nums text-forest-700 sm:w-32">{{ item.time }}</span>
                  <span class="min-w-0">
                    <span class="block text-stone-800">{{ item.title }}</span>
                    <span
                      v-if="item.note"
                      class="mt-0.5 block text-sm text-stone-500"
                    >
                      {{ item.note }}
                    </span>
                  </span>
                </li>
              </ul>
            </div>
          </section>

          <section class="on-dark mt-8 overflow-hidden rounded-2xl bg-forest-800 text-forest-100">
            <div class="p-5 sm:p-7">
              <h2 class="font-display text-2xl text-white">
                Стоимость и оплата
              </h2>

              <dl class="mt-5 space-y-2.5">
                <div class="flex items-baseline justify-between gap-4 border-b border-forest-700 pb-2.5">
                  <dt>Полная стоимость</dt>
                  <dd class="font-display text-2xl text-white">
                    {{ event.priceFormatted }}
                  </dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 border-b border-forest-700 pb-2.5">
                  <dt>Предоплата при бронировании</dt>
                  <dd class="font-medium text-white">
                    {{ detail.depositFormatted }}
                  </dd>
                </div>
                <div class="flex items-baseline justify-between gap-4">
                  <dt>Остаток на месте</dt>
                  <dd class="font-medium text-white">
                    {{ detail.remainingFormatted }}
                  </dd>
                </div>
              </dl>

              <p class="mt-5 flex items-center gap-2.5 text-sm text-forest-200">
                <UIcon
                  name="i-lucide-shield"
                  class="size-4.5 shrink-0 text-forest-400"
                />
                Предоплату можно вернуть до {{ formatDay(detail.refundDeadline) }} включительно.
              </p>
            </div>
          </section>
        </div>

        <aside class="lg:sticky lg:top-24 lg:self-start">
          <ShowcaseLeadForm
            :event-id="event.id"
            :master-id="event.masterId"
            title="Забронировать место"
            note="Заявка ни к чему не обязывает — сначала созвонимся и обсудим детали."
          />
        </aside>
      </div>
    </div>
  </div>
</template>
