<script setup lang="ts">
useHead({ title: 'Мастера и мероприятия' })

const { fetchHome } = useShowcaseApi()
const { data } = await useAsyncData('home', fetchHome)

const masters = computed(() => data.value?.masters ?? [])
const events = computed(() => data.value?.events ?? [])

// В референсах на этом месте стоит фотография. Своих снимков нет, а сток
// под лицензией на коммерческий сайт тащить не стоит — поэтому герой держит
// не картинка, а ближайшая реальная дата. Заодно она полезнее декорации.
const featured = computed(() => events.value[0] ?? null)
const rest = computed(() => events.value.slice(1))

const promises = [
  {
    icon: 'i-lucide-check',
    title: 'Цена сразу на странице',
    text: 'Без «уточняйте в директе» — стоимость и размер предоплаты видны до заявки.'
  },
  {
    icon: 'i-lucide-shield',
    title: 'Возврат предоплаты',
    text: 'Планы меняются: до указанной даты предоплата возвращается полностью.'
  },
  {
    icon: 'i-lucide-users',
    title: 'Малые группы',
    text: 'Количество мест указано у мероприятия и не растёт по ходу набора.'
  },
  {
    icon: 'i-lucide-clock',
    title: 'Ответ за сутки',
    text: 'Заявка ни к чему не обязывает — сначала созвон, потом решение.'
  }
]

const steps = [
  {
    icon: 'i-lucide-search',
    title: 'Выбираете формат',
    text: 'Разовое занятие, личная консультация или выездная программа на несколько дней.'
  },
  {
    icon: 'i-lucide-mail',
    title: 'Оставляете заявку',
    text: 'Мастер связывается в течение суток, отвечает на вопросы и подтверждает место.'
  },
  {
    icon: 'i-lucide-heart',
    title: 'Бронируете предоплатой',
    text: 'Остаток оплачивается на месте. До указанной даты предоплата возвращается.'
  }
]
</script>

<template>
  <div>
    <!-- ── Герой ── -->
    <section class="relative overflow-hidden border-b border-forest-100 bg-gradient-to-b from-forest-50/70 to-paper">
      <BotanicalMark
        class="pointer-events-none absolute -left-24 -top-16 hidden size-120 text-forest-200/60 lg:block"
        variant="branch"
      />

      <div class="relative mx-auto grid max-w-page items-center gap-10 px-6 py-16 lg:grid-cols-[minmax(0,1fr)_minmax(0,26rem)]">
        <div class="hero-stage">
          <p class="hero-item flex items-center gap-2 text-sm uppercase tracking-[0.18em] text-forest-600">
            <UIcon
              name="i-lucide-leaf"
              class="size-4"
            />
            Площадка мастеров
          </p>

          <h1 class="hero-item mt-4 max-w-2xl text-balance font-display text-4xl leading-[1.1] tracking-tight text-forest-900 sm:text-5xl lg:text-6xl lg:leading-[1.08]">
            Практики и ретриты
            <span class="text-coral-700">с открытой ценой</span>
          </h1>

          <p class="hero-item mt-5 max-w-xl text-lg leading-relaxed text-stone-600">
            Каждый мастер ведёт свою страницу: что проводит, когда ближайший набор,
            сколько стоит участие и на каких условиях возвращается предоплата.
          </p>

          <div class="hero-item mt-7 flex flex-wrap gap-3">
            <UButton
              to="/events"
              color="secondary"
              size="xl"
              trailing-icon="i-lucide-arrow-right"
              class="rounded-full"
            >
              Ближайшие мероприятия
            </UButton>
            <UButton
              to="/masters"
              color="primary"
              variant="outline"
              size="xl"
              class="rounded-full"
            >
              Все мастера
            </UButton>
          </div>
        </div>

        <!-- Ближайшая дата вместо декоративного снимка -->
        <aside
          v-if="featured"
          class="hero-item relative"
        >
          <div class="rounded-3xl bg-white p-6 shadow-[0_24px_48px_-28px] shadow-forest-900/40 ring-1 ring-forest-100">
            <p class="text-xs uppercase tracking-[0.16em] text-forest-600">
              Ближайшая дата
            </p>

            <h2 class="mt-2.5 font-display text-2xl leading-snug text-forest-900">
              {{ featured.title }}
            </h2>

            <dl class="mt-4 space-y-2.5 text-sm text-stone-600">
              <div class="flex items-center gap-2.5">
                <dt class="sr-only">
                  Даты
                </dt>
                <UIcon
                  name="i-lucide-calendar"
                  class="size-4.5 shrink-0 text-forest-500"
                />
                <dd>{{ formatRange(featured.startsAt, featured.endsAt) }}</dd>
              </div>
              <div
                v-if="featured.location"
                class="flex items-center gap-2.5"
              >
                <dt class="sr-only">
                  Место
                </dt>
                <UIcon
                  name="i-lucide-map-pin"
                  class="size-4.5 shrink-0 text-forest-500"
                />
                <dd>{{ featured.location }}</dd>
              </div>
              <div
                v-if="featured.masterName"
                class="flex items-center gap-2.5"
              >
                <dt class="sr-only">
                  Мастер
                </dt>
                <UIcon
                  name="i-lucide-users"
                  class="size-4.5 shrink-0 text-forest-500"
                />
                <dd>{{ featured.masterName }}</dd>
              </div>
            </dl>

            <div class="mt-5 flex items-end justify-between border-t border-forest-100 pt-4">
              <div>
                <p class="text-xs text-stone-500">
                  Участие
                </p>
                <p class="font-display text-2xl text-forest-900">
                  {{ featured.priceFormatted }}
                </p>
              </div>
              <UButton
                :to="`/events/${featured.slug}`"
                color="primary"
                trailing-icon="i-lucide-arrow-right"
                class="rounded-full"
              >
                Подробнее
              </UButton>
            </div>
          </div>
        </aside>
      </div>
    </section>

    <!-- ── Полоса обещаний ──
         Не карточки: ряд с разделителями читается как один свод правил
         площадки, а четыре одинаковые плитки — как четыре разных объекта. -->
    <section class="border-b border-forest-100 bg-paper-warm">
      <div class="mx-auto max-w-page px-6 py-10">
        <ul class="grid gap-y-7 sm:grid-cols-2 lg:grid-cols-4">
          <li
            v-for="(promise, index) in promises"
            :key="promise.title"
            class="flex gap-3.5 lg:px-7 first:lg:pl-0 last:lg:pr-0"
            :class="index > 0 ? 'lg:border-l lg:border-forest-200' : ''"
          >
            <UIcon
              :name="promise.icon"
              class="mt-0.5 size-6 shrink-0 text-forest-600"
            />
            <div>
              <h3 class="font-display text-lg text-forest-900">
                {{ promise.title }}
              </h3>
              <p class="mt-1 text-sm leading-relaxed text-stone-600">
                {{ promise.text }}
              </p>
            </div>
          </li>
        </ul>
      </div>
    </section>

    <!-- ── Ближайшие мероприятия ── -->
    <section
      v-if="rest.length"
      class="mx-auto max-w-page px-6 py-16"
    >
      <div class="flex flex-wrap items-end justify-between gap-4">
        <h2 class="font-display text-3xl tracking-tight text-forest-900 sm:text-4xl">
          Ближайшие мероприятия
        </h2>
        <UButton
          to="/events"
          color="primary"
          variant="outline"
          trailing-icon="i-lucide-arrow-right"
          class="rounded-full"
        >
          Смотреть все
        </UButton>
      </div>

      <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <ShowcaseEventCard
          v-for="event in rest"
          :key="event.id"
          :event="event"
        />
      </div>
    </section>

    <!-- ── Как это устроено ──
         Нумерация здесь заслужена: это последовательность, а не список —
         человеку важен порядок шагов и то, что деньги идут последними. -->
    <section class="on-dark relative overflow-hidden bg-forest-800 text-forest-100">
      <BotanicalMark
        class="pointer-events-none absolute -bottom-24 -right-16 size-104 text-forest-600/30"
        variant="branch"
      />

      <div class="relative mx-auto max-w-page px-6 py-16">
        <h2 class="max-w-xl text-balance font-display text-3xl tracking-tight text-white sm:text-4xl">
          Как это устроено
        </h2>
        <p class="mt-3 max-w-lg text-forest-200">
          От выбора формата до брони — три шага, и ни на одном из них
          вы ничего не теряете, если передумаете.
        </p>

        <div class="relative mt-11">
          <!-- Пунктир связывает шаги в путь. Лежит вне списка: внутри ol
               он был бы четвёртым пунктом для скринридера. На узких
               экранах шаги идут колонкой, и связь не нужна. -->
          <div
            class="pointer-events-none absolute left-[16%] right-[16%] top-8 hidden border-t border-dashed border-forest-600 md:block"
            aria-hidden="true"
          />

          <ol class="relative grid gap-9 md:grid-cols-3">
            <li
              v-for="(step, index) in steps"
              :key="step.title"
            >
              <div class="flex size-16 items-center justify-center rounded-full bg-forest-700 ring-4 ring-forest-800">
                <UIcon
                  :name="step.icon"
                  class="size-6 text-forest-100"
                />
              </div>
              <p class="mt-4 text-sm text-forest-300">
                Шаг {{ index + 1 }}
              </p>
              <h3 class="mt-1 font-display text-2xl text-white">
                {{ step.title }}
              </h3>
              <p class="mt-2 max-w-xs leading-relaxed text-forest-200">
                {{ step.text }}
              </p>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <!-- ── Мастера ── -->
    <section
      v-if="masters.length"
      class="mx-auto max-w-page px-6 py-16"
    >
      <div class="flex flex-wrap items-end justify-between gap-4">
        <h2 class="font-display text-3xl tracking-tight text-forest-900 sm:text-4xl">
          Мастера площадки
        </h2>
        <UButton
          to="/masters"
          color="primary"
          variant="outline"
          trailing-icon="i-lucide-arrow-right"
          class="rounded-full"
        >
          Смотреть всех
        </UButton>
      </div>

      <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <ShowcaseMasterCard
          v-for="master in masters"
          :key="master.id"
          :master="master"
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
/*
 * Единственный поставленный момент на странице — вход героя. Дальше по
 * скроллу ничего не появляется: одинаковая анимация на каждой секции
 * читается как шум, а не как замысел.
 */
.hero-item {
  animation: hero-rise 0.7s cubic-bezier(0.16, 1, 0.3, 1) backwards;
}

.hero-stage .hero-item:nth-child(1) { animation-delay: 0.04s; }
.hero-stage .hero-item:nth-child(2) { animation-delay: 0.12s; }
.hero-stage .hero-item:nth-child(3) { animation-delay: 0.2s; }
.hero-stage .hero-item:nth-child(4) { animation-delay: 0.28s; }

aside.hero-item { animation-delay: 0.34s; }

@keyframes hero-rise {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-item {
    animation: none;
  }
}
</style>
