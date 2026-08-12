<script setup lang="ts">
import type { BadgeProps } from '@nuxt/ui'
import type { MasterLead } from '~/types'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Заявка'
})

/**
 * Одна заявка целиком.
 *
 * Отдельным экраном, а не раскрытой строкой: история переписки со временем
 * длиннее самой заявки, а на страницу можно дать ссылку — «посмотри, что
 * с этим человеком» без объяснений, где его искать в списке.
 */
const route = useRoute()
const id = computed(() => Number(route.params.id))

const { fetchLead, fetchLeads } = useMasterApi()

const { data: lead } = await useAsyncData(
  () => `master-lead-${id.value}`,
  () => fetchLead(id.value)
)

if (!lead.value) {
  throw createError({ statusCode: 404, statusMessage: 'Заявка не найдена', fatal: true })
}

// Список статусов живёт в ответе списка — берём оттуда же, чтобы набор
// и подписи не разъезжались между экранами.
const { data: page } = await useAsyncData('master-lead-statuses', () => fetchLeads({ perPage: 1 }))
const statuses = computed(() => page.value?.statuses ?? [])

const dialogOpen = ref(false)

function applySaved(saved: MasterLead): void {
  lead.value = saved
}
</script>

<template>
  <div
    v-if="lead"
    class="max-w-3xl space-y-6"
  >
    <UButton
      to="/master/leads"
      color="neutral"
      variant="ghost"
      icon="i-lucide-arrow-left"
      class="-ms-2"
    >
      Все заявки
    </UButton>

    <div class="rounded-xl border border-default p-5">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <h1 class="flex items-center gap-2 font-display text-2xl text-highlighted">
            {{ lead.name }}
            <UIcon
              v-if="lead.fromMember"
              name="i-lucide-user-check"
              class="size-5 shrink-0 text-primary"
              title="Оставил вошедший участник"
            />
          </h1>

          <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
            <a
              v-if="lead.phone"
              :href="`tel:${lead.phone}`"
              class="inline-flex items-center gap-1.5 text-primary hover:underline"
            >
              <UIcon
                name="i-lucide-phone"
                class="size-4"
              />
              {{ lead.phone }}
            </a>
            <a
              v-if="lead.email"
              :href="`mailto:${lead.email}`"
              class="inline-flex items-center gap-1.5 text-muted hover:underline"
            >
              <UIcon
                name="i-lucide-mail"
                class="size-4"
              />
              {{ lead.email }}
            </a>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <UBadge
            :color="(lead.statusColor as BadgeProps['color'])"
            variant="subtle"
            size="lg"
          >
            {{ lead.statusLabel }}
          </UBadge>
          <UButton
            color="secondary"
            size="lg"
            icon="i-lucide-pencil-line"
            class="rounded-full"
            @click="dialogOpen = true"
          >
            Изменить статус
          </UButton>
        </div>
      </div>

      <dl class="mt-5 grid gap-4 border-t border-default pt-5 sm:grid-cols-2">
        <div>
          <dt class="text-sm text-muted">
            Мероприятие
          </dt>
          <dd class="mt-0.5">
            <NuxtLink
              v-if="lead.eventSlug"
              :to="`/events/${lead.eventSlug}`"
              target="_blank"
              class="text-primary hover:underline"
            >
              {{ lead.eventTitle }}
            </NuxtLink>
            <span
              v-else
              class="text-dimmed"
            >Со страницы мастера</span>
          </dd>
        </div>
        <div>
          <dt class="text-sm text-muted">
            Пришла
          </dt>
          <dd class="mt-0.5 text-toned">
            {{ formatDate(lead.createdAt, true) }}
          </dd>
        </div>
      </dl>

      <div
        v-if="lead.message"
        class="mt-5 border-t border-default pt-5"
      >
        <p class="text-sm text-muted">
          Сообщение
        </p>
        <p class="mt-1.5 whitespace-pre-line text-toned">
          {{ lead.message }}
        </p>
      </div>
    </div>

    <!-- ── История ── -->
    <section>
      <h2 class="font-display text-xl text-highlighted">
        История
      </h2>

      <ol
        v-if="lead.history.length"
        class="mt-3 space-y-4 border-s border-default ps-5"
      >
        <li
          v-for="change in lead.history"
          :key="change.id"
          class="relative"
        >
          <span class="absolute -start-[1.6rem] top-1.5 size-2.5 rounded-full bg-accented" />

          <p class="flex flex-wrap items-center gap-1.5">
            <template v-if="change.fromLabel">
              <span class="text-dimmed">{{ change.fromLabel }}</span>
              <UIcon
                name="i-lucide-arrow-right"
                class="size-3.5 text-dimmed"
              />
            </template>
            <UBadge
              :color="(change.toColor as BadgeProps['color'])"
              variant="subtle"
            >
              {{ change.toLabel }}
            </UBadge>
          </p>

          <p
            v-if="change.comment"
            class="mt-1.5 whitespace-pre-line rounded-lg bg-elevated px-3 py-2 text-toned"
          >
            {{ change.comment }}
          </p>

          <p class="mt-1 text-sm text-dimmed">
            {{ formatDate(change.createdAt, true) }}<template v-if="change.author">
              · {{ change.author }}
            </template>
          </p>
        </li>
      </ol>

      <p
        v-else
        class="mt-3 rounded-xl border border-dashed border-default px-5 py-8 text-center text-muted"
      >
        Статус ещё не меняли — заявка как пришла, так и лежит.
      </p>
    </section>

    <MasterLeadStatusDialog
      v-model:open="dialogOpen"
      :lead="lead"
      :statuses="statuses"
      @saved="applySaved"
    />
  </div>
</template>
