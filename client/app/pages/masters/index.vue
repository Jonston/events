<script setup lang="ts">
useHead({ title: 'Мастера' })

const { fetchMasters } = useShowcaseApi()
const { data } = await useAsyncData('masters', fetchMasters)

const masters = computed(() => data.value?.data ?? [])
</script>

<template>
  <div>
    <PageSection class="border-b border-forest-100 bg-gradient-to-b from-forest-50/70 to-paper py-11">
      <header class="max-w-2xl">
        <h1 class="text-balance font-display text-4xl tracking-tight text-forest-900 sm:text-5xl">
          Мастера
        </h1>
        <p class="mt-3 text-lg leading-relaxed text-stone-600">
          Практики, консультации и выездные программы. У каждого мастера — своя
          страница с ближайшими датами и условиями участия.
        </p>
      </header>
    </PageSection>

    <!-- Заголовок страницы уже сказал «Мастера» — над лентой он повторялся бы
         через два сантиметра. -->
    <ShowcaseCarousel
      v-slot="{ item }"
      :items="masters"
      class="py-9"
    >
      <ShowcaseMasterCard
        :master="item"
        class="h-full"
      />
    </ShowcaseCarousel>

    <PageSection
      v-if="!masters.length"
      class="py-11"
    >
      <div class="rounded-3xl bg-white px-6 py-14 text-center ring-1 ring-forest-100">
        <BotanicalMark
          class="mx-auto size-24 text-forest-300"
          variant="branch"
        />
        <h2 class="mt-5 font-display text-2xl text-forest-900">
          Пока пусто
        </h2>
        <p class="mx-auto mt-2 max-w-sm text-stone-600">
          Ни одного мастера ещё не опубликовано.
        </p>
      </div>
    </PageSection>
  </div>
</template>
