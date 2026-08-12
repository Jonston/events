<script setup lang="ts">
import type { LeadStatus, MasterLead } from '~/types'

/**
 * Смена статуса заявки с комментарием.
 *
 * Отдельным окном, а не выпадающим списком в строке: статус меняет не только
 * подпись — бронь занимает место в группе, — и такое действие стоит показать
 * человеку целиком, вместе с тем, что уже происходило с заявкой.
 */
const props = defineProps<{
  lead: MasterLead | null
  statuses: LeadStatus[]
}>()

const open = defineModel<boolean>('open', { default: false })

const emit = defineEmits<{ saved: [lead: MasterLead] }>()

const { setLeadStatus } = useMasterApi()
const toast = useToast()

const status = ref('')
const comment = ref('')
const saving = ref(false)
const failure = ref<string | null>(null)

// Окно открывается на конкретной заявке: поля должны показывать её,
// а не то, что осталось от предыдущей.
watch(() => props.lead, (lead) => {
  status.value = lead?.status ?? ''
  comment.value = ''
  failure.value = null
}, { immediate: true })

const history = computed(() => props.lead?.history ?? [])

async function submit() {
  if (!props.lead) return

  saving.value = true
  failure.value = null

  try {
    const saved = await setLeadStatus(props.lead.id, status.value, comment.value.trim() || undefined)

    emit('saved', saved)
    open.value = false
    toast.add({ title: 'Статус сохранён', description: saved.statusLabel, color: 'success' })
  } catch (error: unknown) {
    const problem = error as { data?: { errors?: Record<string, string[]>, message?: string } }

    // Отказ по местам приходит ошибкой поля — показываем его прямо здесь,
    // а не всплывающей подсказкой, которая исчезнет раньше, чем её прочтут.
    failure.value = problem?.data?.errors?.status?.[0] ?? problem?.data?.message ?? 'Не получилось сохранить.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="lead ? `Заявка от ${lead.name}` : 'Заявка'"
    :description="lead?.eventTitle ? `Мероприятие «${lead.eventTitle}»` : 'Заявка со страницы мастера'"
  >
    <template #body>
      <div class="space-y-5">
        <UFormField
          label="Статус"
          name="status"
        >
          <USelect
            v-model="status"
            :items="statuses"
            value-key="value"
            label-key="label"
            size="lg"
            class="w-full"
          />
        </UFormField>

        <UFormField
          label="Комментарий"
          name="comment"
          description="Останется в истории заявки. Через месяц «почему сняли бронь» не помнит никто."
        >
          <UTextarea
            v-model="comment"
            :rows="3"
            size="lg"
            class="w-full"
            placeholder="Перенесли на сентябрь, деньги вернули."
          />
        </UFormField>

        <UAlert
          v-if="failure"
          color="error"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          :description="failure"
        />

        <div v-if="history.length">
          <p class="text-sm font-medium text-highlighted">
            История
          </p>
          <ol class="mt-2 space-y-2 border-l border-default pl-4">
            <li
              v-for="change in history"
              :key="change.id"
              class="text-sm"
            >
              <p class="text-toned">
                <template v-if="change.fromLabel">
                  {{ change.fromLabel }} →
                </template>{{ change.toLabel }}
              </p>
              <p
                v-if="change.comment"
                class="mt-0.5 whitespace-pre-line text-muted"
              >
                {{ change.comment }}
              </p>
              <p class="mt-0.5 text-xs text-dimmed">
                {{ formatDate(change.createdAt, true) }}<template v-if="change.author">
                  · {{ change.author }}
                </template>
              </p>
            </li>
          </ol>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex justify-end gap-3">
        <UButton
          color="neutral"
          variant="ghost"
          size="lg"
          @click="open = false"
        >
          Отмена
        </UButton>
        <UButton
          color="secondary"
          size="lg"
          :loading="saving"
          class="rounded-full"
          @click="submit"
        >
          Сохранить
        </UButton>
      </div>
    </template>
  </UModal>
</template>
