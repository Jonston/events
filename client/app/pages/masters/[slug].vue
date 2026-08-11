<script setup lang="ts">
const route = useRoute()
const slug = computed(() => String(route.params.slug))

const { fetchMaster } = useShowcaseApi()
const { data } = await useAsyncData(
  () => `master-${slug.value}`,
  () => fetchMaster(slug.value)
)

if (!data.value) {
  throw createError({ statusCode: 404, statusMessage: 'Мастер не найден', fatal: true })
}

const master = computed(() => data.value!.card)
const events = computed(() => data.value!.events)
const reviews = computed(() => data.value!.reviews)

useHead({ title: () => master.value.name })

const contactOpen = ref(false)
</script>

<template>
  <div>
    <!-- ── Путь ──
         Живёт над шапкой, а не внутри: в шапке он спорил бы за верхний
         левый угол с фотографией, а служебная строка навигации не стоит
         того, чтобы её читали первой. -->
    <nav
      class="border-b border-forest-100 bg-paper"
      aria-label="Хлебные крошки"
    >
      <div class="mx-auto flex max-w-page items-center gap-2 px-6 py-3 text-sm text-stone-500">
        <NuxtLink
          to="/masters"
          class="transition-colors hover:text-forest-800"
        >
          Мастера
        </NuxtLink>
        <span
          aria-hidden="true"
          class="text-forest-300"
        >/</span>
        <span class="text-forest-800">{{ master.name }}</span>
      </div>
    </nav>

    <!-- ── Шапка мастера ──
         Имя ложится в левый нижний угол снимка — там же, где оно лежало бы
         на подписи к фотографии. Затемнение снизу не декоративное: обложку
         грузит мастер, светлый кадр здесь так же вероятен, как тёмный,
         и белый текст должен читаться на любом. -->
    <header class="on-dark relative isolate overflow-hidden bg-forest-950 text-white">
      <div class="absolute inset-0">
        <img
          v-if="master.cover"
          :src="master.cover"
          alt=""
          class="size-full object-cover"
        >
        <BotanicalScene
          v-else
          :seed="master.id"
          class="size-full"
        />
      </div>

      <div class="absolute inset-0 bg-gradient-to-t from-forest-950 via-forest-950/70 via-45% to-forest-950/25" />
      <div class="absolute inset-0 bg-gradient-to-r from-forest-950/70 via-forest-950/10 to-transparent" />

      <div class="relative mx-auto flex min-h-80 max-w-page flex-col justify-end px-6 pb-8 pt-16 sm:min-h-96 sm:pb-10 lg:min-h-[28rem]">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
          <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:gap-6">
            <!-- Без снимка это не заглушка-«монета», а стеклянный кружок
                 с ботанической меткой: он держит место фото, не притворяясь им. -->
            <div class="relative size-24 shrink-0 overflow-hidden rounded-full bg-white/10 ring-1 ring-white/30 backdrop-blur-md sm:size-28">
              <img
                v-if="master.photo"
                :src="master.photo"
                :alt="master.name"
                class="size-full object-cover"
              >
              <BotanicalMark
                v-else
                class="absolute inset-0 m-auto size-3/5 text-forest-100/85"
                variant="seal"
              />
            </div>

            <div class="min-w-0">
              <h1 class="text-balance font-display text-4xl leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
                {{ master.name }}
              </h1>
              <p
                v-if="master.headline"
                class="mt-3 max-w-xl text-lg leading-relaxed text-forest-100"
              >
                {{ master.headline }}
              </p>

              <p
                v-if="master.rating"
                class="mt-3 inline-flex items-center gap-2 text-sm"
              >
                <RatingStars
                  :value="master.rating.value"
                  :size="17"
                  class="text-coral-400"
                />
                <span class="font-medium tabular-nums text-white">{{ master.rating.value.toFixed(1) }}</span>
                <span class="text-forest-200">
                  · {{ master.rating.count }} {{ plural(master.rating.count, 'отзыв', 'отзыва', 'отзывов') }}
                </span>
              </p>
            </div>
          </div>

          <!-- Заявка — единственное целевое действие страницы, поэтому
               кнопка замыкает шапку справа внизу: взгляд доходит до неё
               после имени и описания, а не спорит с ними за внимание. -->
          <UButton
            color="secondary"
            size="xl"
            icon="i-lucide-mail"
            class="w-full shrink-0 justify-center rounded-full sm:w-auto sm:self-end"
            @click="contactOpen = true"
          >
            Написать мастеру
          </UButton>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-page px-6 py-11">
      <p
        v-if="data?.bio"
        class="max-w-[68ch] whitespace-pre-line text-lg leading-relaxed text-stone-700"
      >
        {{ data.bio }}
      </p>

      <section class="mt-10">
        <h2 class="font-display text-3xl text-forest-900">
          Ближайшие мероприятия
        </h2>

        <div
          v-if="events.length"
          class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
        >
          <ShowcaseEventCard
            v-for="event in events"
            :key="event.id"
            :event="event"
            :show-master="false"
          />
        </div>

        <div
          v-else
          class="mt-6 rounded-2xl bg-white px-6 py-10 text-center ring-1 ring-forest-100"
        >
          <BotanicalMark
            class="mx-auto size-20 text-forest-300"
            variant="seal"
          />
          <p class="mx-auto mt-4 max-w-sm text-stone-600">
            Ближайших дат пока нет — оставьте заявку, и мастер сообщит о наборе.
          </p>
          <UButton
            color="secondary"
            size="lg"
            class="mt-5 rounded-full"
            @click="contactOpen = true"
          >
            Оставить заявку
          </UButton>
        </div>
      </section>

      <!-- ── Отзывы ──
           Раздел появляется, когда в нём есть что читать. Пустой блок
           «отзывов пока нет» — это строка, которую посетитель читает
           на каждой странице и ни разу не использует. -->
      <section
        v-if="reviews.length"
        class="mt-12"
      >
        <h2 class="font-display text-3xl text-forest-900">
          Отзывы
        </h2>

        <ul class="mt-6 grid gap-5 lg:grid-cols-2">
          <li
            v-for="review in reviews"
            :key="review.id"
            class="rounded-2xl bg-white p-5 ring-1 ring-forest-100"
          >
            <div class="flex items-center justify-between gap-3">
              <span class="font-medium text-forest-900">{{ review.author }}</span>
              <RatingStars
                :value="review.rating"
                :size="15"
                class="text-coral-600"
              />
            </div>
            <p
              v-if="review.body"
              class="mt-2.5 leading-relaxed text-stone-600"
            >
              {{ review.body }}
            </p>
            <p class="mt-2.5 text-sm text-stone-400">
              {{ formatDay(review.createdAt) }}
            </p>
          </li>
        </ul>
      </section>
    </div>

    <UModal
      v-model:open="contactOpen"
      title="Написать мастеру"
      :ui="{ content: 'max-w-lg' }"
    >
      <template #body>
        <ShowcaseLeadForm
          :master-id="master.id"
          :title="master.name"
          note="Заявка ни к чему не обязывает — сначала созвонимся и обсудим детали."
          class="p-0 shadow-none ring-0"
        />
      </template>
    </UModal>
  </div>
</template>
