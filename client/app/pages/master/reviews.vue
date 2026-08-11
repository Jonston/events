<script setup lang="ts">
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Отзывы'
})

const { fetchReviews } = useMasterApi()
const { data, status } = await useAsyncData('master-reviews', fetchReviews)

const reviews = computed(() => data.value?.data ?? [])
const pending = computed(() => status.value === 'pending')

const statusColor: Record<string, 'warning' | 'success' | 'error'> = {
  pending: 'warning',
  published: 'success',
  rejected: 'error'
}
</script>

<template>
  <div>
    <p class="mb-5 text-sm text-muted">
      Отзывы вы не правите и не удаляете — иначе оценка перестала бы что-либо
      значить. Спорные снимает площадка. Здесь видны и те, что ещё на проверке.
    </p>

    <div
      v-if="pending"
      class="flex justify-center py-12"
    >
      <UIcon
        name="i-lucide-loader-circle"
        class="animate-spin text-3xl text-dimmed"
      />
    </div>

    <div
      v-else-if="!reviews.length"
      class="rounded-xl border border-dashed border-default px-6 py-14 text-center"
    >
      <BotanicalMark
        class="mx-auto size-20 text-forest-300"
        variant="seal"
      />
      <p class="mt-4 font-medium text-highlighted">
        Отзывов пока нет
      </p>
      <p class="mx-auto mt-1 max-w-sm text-sm text-muted">
        Их оставляют участники после занятий.
      </p>
    </div>

    <ul
      v-else
      class="grid gap-3 lg:grid-cols-2"
    >
      <li
        v-for="review in reviews"
        :key="review.id"
        class="rounded-xl border border-default p-4"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="font-medium text-highlighted">{{ review.author }}</span>
          <div class="flex items-center gap-2">
            <RatingStars
              :value="review.rating"
              :size="15"
              class="text-coral-600"
            />
            <UBadge
              :color="statusColor[review.status] ?? 'neutral'"
              variant="subtle"
              size="sm"
            >
              {{ review.statusLabel }}
            </UBadge>
          </div>
        </div>

        <p
          v-if="review.body"
          class="mt-2.5 leading-relaxed"
        >
          {{ review.body }}
        </p>

        <p class="mt-2.5 text-sm text-muted">
          {{ formatDate(review.createdAt) }}
        </p>
      </li>
    </ul>
  </div>
</template>
