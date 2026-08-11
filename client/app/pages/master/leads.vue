<script setup lang="ts">
import type { MasterLead } from '~/types'

definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  role: 'master',
  layout: 'cabinet',
  pageTitle: 'Заявки ко мне'
})

const { fetchLeads, setLeadStatus } = useMasterApi()
const toast = useToast()

const { data, status: loading } = await useAsyncData('master-leads', fetchLeads)

const leads = ref<MasterLead[]>([])
watchEffect(() => {
  leads.value = data.value?.data ?? []
})

const statuses = computed(() => data.value?.statuses ?? [])
const pending = computed(() => loading.value === 'pending')

const statusColor: Record<string, 'primary' | 'warning' | 'success' | 'neutral' | 'error'> = {
  new: 'primary',
  in_progress: 'warning',
  booked: 'success',
  completed: 'neutral',
  rejected: 'error'
}

async function changeStatus(lead: MasterLead, value: string) {
  const previous = lead.status
  lead.status = value

  try {
    const result = await setLeadStatus(lead.id, value)
    lead.statusLabel = result.statusLabel
  } catch {
    // Возвращаем как было: показывать статус, которого нет на сервере,
    // хуже, чем показать неудачу.
    lead.status = previous
    toast.add({ title: 'Статус не сохранился', color: 'error' })
  }
}
</script>

<template>
  <div>
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
      v-else-if="!leads.length"
      class="rounded-xl border border-dashed border-default px-6 py-14 text-center"
    >
      <BotanicalMark
        class="mx-auto size-20 text-forest-300"
        variant="branch"
      />
      <p class="mt-4 font-medium text-highlighted">
        Заявок пока нет
      </p>
      <p class="mx-auto mt-1 max-w-sm text-sm text-muted">
        Они появятся здесь, как только кто-то оставит контакт на вашей странице
        или на странице мероприятия.
      </p>
    </div>

    <ul
      v-else
      class="space-y-3"
    >
      <li
        v-for="lead in leads"
        :key="lead.id"
        class="rounded-xl border border-default p-4"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="font-medium text-highlighted">
              {{ lead.name }}
            </p>
            <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
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
                class="inline-flex items-center gap-1.5 text-primary hover:underline"
              >
                <UIcon
                  name="i-lucide-mail"
                  class="size-4"
                />
                {{ lead.email }}
              </a>
            </p>
          </div>

          <div class="flex items-center gap-2">
            <UBadge
              :color="statusColor[lead.status] ?? 'neutral'"
              variant="subtle"
              size="lg"
            >
              {{ lead.statusLabel }}
            </UBadge>
            <USelect
              :model-value="lead.status"
              :items="statuses"
              value-key="value"
              label-key="label"
              size="sm"
              class="w-40"
              :aria-label="`Статус заявки от ${lead.name}`"
              @update:model-value="value => changeStatus(lead, String(value))"
            />
          </div>
        </div>

        <p
          v-if="lead.message"
          class="mt-3 whitespace-pre-line rounded-lg bg-elevated px-3 py-2 text-sm"
        >
          {{ lead.message }}
        </p>

        <p class="mt-3 text-sm text-muted">
          <template v-if="lead.eventTitle">
            С мероприятия «{{ lead.eventTitle }}» ·
          </template>
          {{ formatDate(lead.createdAt, true) }}
        </p>
      </li>
    </ul>
  </div>
</template>
