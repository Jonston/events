<script setup lang="ts">
import type { UIMessage } from 'ai'

/**
 * Помощник кабинета: выдвижная панель справа.
 *
 * Панель, а не отдельная страница: спрашивают на ходу — «где заявки»,
 * «сколько осталось мест», — и уводить человека со страницы ради вопроса
 * значило бы терять то, что он делал. Здесь же живут и переходы: агент
 * называет раздел, кабинет уезжает туда, а разговор остаётся открытым.
 */
const open = ref(false)
const input = ref('')

const { messages, status, error, sendMessage, stop, historyPending, loadHistory, reset } = useCabinetAssistant()

const busy = computed(() => status.value === 'submitted' || status.value === 'streaming')

// История нужна только тому, кто панель открыл: за закрытой она не грузится.
watch(open, (isOpen) => {
  if (isOpen) loadHistory()
})

const suggestions = [
  'Где поменять телефон?',
  'Что идёт на площадке в ближайшее время?',
  'Открой мои данные'
]

function ask(text: string): void {
  if (busy.value) return

  input.value = ''
  sendMessage({ text })
}

function submit(): void {
  const text = input.value.trim()

  if (text) ask(text)
}

/** Части ответа: текст показываем как есть, вызовы инструментов — строкой. */
function partsOf(message: UIMessage) {
  return message.parts ?? []
}

function navigationLabel(part: unknown): string {
  const target = parseTarget((part as { output?: unknown }).output)

  return target?.label ? `Открываю «${target.label}»` : 'Ищу нужный раздел'
}
</script>

<template>
  <USlideover
    v-model:open="open"
    title="Помощник"
    description="Подскажет по кабинету и отведёт в нужный раздел"
    :ui="{ content: 'max-w-3xl' }"
  >
    <UButton
      icon="i-lucide-bot"
      color="neutral"
      variant="ghost"
      aria-label="Помощник"
    />

    <template #body>
      <div
        v-if="historyPending"
        class="flex justify-center py-6"
      >
        <UIcon
          name="i-lucide-loader-circle"
          class="size-5 animate-spin text-muted"
        />
      </div>

      <div
        v-else-if="!messages.length"
        class="space-y-4"
      >
        <p class="text-sm text-muted">
          Спросите про кабинет или про мероприятия площадки — помощник ответит
          и, если нужно, сам откроет раздел.
        </p>

        <div class="flex flex-wrap gap-2">
          <UButton
            v-for="suggestion in suggestions"
            :key="suggestion"
            color="neutral"
            variant="outline"
            size="sm"
            class="rounded-full"
            @click="ask(suggestion)"
          >
            {{ suggestion }}
          </UButton>
        </div>
      </div>

      <UChatMessages
        v-else
        :messages="messages"
        :status="status"
        should-auto-scroll
        :assistant="{ icon: 'i-lucide-bot' }"
        :user="{ variant: 'soft' }"
      >
        <template #content="message">
          <div class="space-y-1.5">
            <template
              v-for="(part, index) in partsOf(message)"
              :key="index"
            >
              <p
                v-if="part.type === 'text'"
                class="whitespace-pre-wrap"
              >
                {{ part.text }}
              </p>

              <UChatTool
                v-else-if="part.type === 'tool-navigate_cabinet'"
                icon="i-lucide-compass"
                :text="navigationLabel(part)"
                :loading="part.state !== 'output-available'"
              />

              <UChatTool
                v-else-if="part.type === 'tool-find_events'"
                icon="i-lucide-calendar-search"
                text="Смотрю витрину мероприятий"
                :loading="part.state !== 'output-available'"
              />
            </template>
          </div>
        </template>
      </UChatMessages>
    </template>

    <template #footer>
      <div class="w-full space-y-3">
        <!-- «Начать заново» живёт у поля ввода, а не в шапке: там его место
             занимает крестик, и две кнопки рядом читались бы как одна пара,
             где вторая тоже закрывает панель. -->
        <div
          v-if="messages.length"
          class="flex justify-end"
        >
          <UButton
            icon="i-lucide-eraser"
            color="neutral"
            variant="link"
            size="xs"
            label="Начать заново"
            @click="reset"
          />
        </div>

        <!-- Ответ приходит потоком, и ошибка провайдера прилетает телом
             запроса: показать её как есть — показать человеку трассировку. -->
        <UAlert
          v-if="error"
          color="error"
          variant="subtle"
          icon="i-lucide-triangle-alert"
          title="Помощник не ответил"
          description="Похоже, модель сейчас недоступна. Попробуйте ещё раз чуть позже."
        />

        <UChatPrompt
          v-model="input"
          placeholder="Спросите помощника…"
          @submit="submit"
        >
          <UChatPromptSubmit
            :status="status"
            @stop="stop"
          />
        </UChatPrompt>
      </div>
    </template>
  </USlideover>
</template>
