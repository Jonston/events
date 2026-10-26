import type { UIMessage } from 'ai'
import { DefaultChatTransport } from 'ai'
import { useChat } from '@ai-sdk/vue'

/** Раздел, который назвал серверный инструмент навигации. */
interface NavigationTarget {
  path: string
  label: string
}

/** Имя инструмента на сервере — из него AI SDK собирает тип части сообщения. */
const NAVIGATION_PART = 'tool-navigate_cabinet'

/**
 * Помощник кабинета: диалог с агентом и его переходы по разделам.
 *
 * Переписку хранит сервер — на клиент приходит только новый ответ, а история
 * подтягивается при открытии панели. Поэтому вкладку можно закрыть и вернуться
 * через день: разговор продолжится, а не начнётся заново.
 */
export function useCabinetAssistant() {
  const nuxtApp = useNuxtApp()
  const { tokenStorage } = useSanctumAppConfig()
  const apiUrl = useRuntimeConfig().public.apiUrl as string
  const api = useApiClient()

  const chat = useChat({
    transport: new DefaultChatTransport({
      api: `${apiUrl}/api/cabinet/assistant`,
      /*
       * Токен подставляется на каждый запрос, а не один раз при создании:
       * панель живёт в оболочке кабинета, и человек успевает перелогиниться,
       * ни разу её не закрыв.
       */
      headers: async (): Promise<Record<string, string>> => {
        const token = await tokenStorage?.get(nuxtApp)

        return token ? { Authorization: `Bearer ${token}` } : {}
      },
      /*
       * Серверу уходит только новый вопрос: переписку он держит у себя,
       * и отправлять её обратно целиком значило бы платить за неё дважды.
       */
      prepareSendMessagesRequest: ({ messages }) => ({
        body: { message: textOf(messages.at(-1)) }
      })
    })
  })

  const historyPending = ref(false)
  const historyLoaded = ref(false)

  /** Переходы, которые уже отработали: второй раз по ним не ходим. */
  const visited = new Set<string>()

  /**
   * История подтягивается по требованию — при первом открытии панели.
   * Кабинет открывают ради дел, а не ради помощника, и платить запросом
   * за каждую страницу незачем.
   */
  async function loadHistory(): Promise<void> {
    if (historyLoaded.value || historyPending.value) return

    historyPending.value = true

    try {
      const { messages } = await api<{ messages: UIMessage[] }>('/api/cabinet/assistant')

      chat.messages.value = messages
      historyLoaded.value = true
    } finally {
      historyPending.value = false
    }
  }

  /** Забыть разговор — и на сервере, и на экране. */
  async function reset(): Promise<void> {
    await api('/api/cabinet/assistant', { method: 'DELETE' })

    chat.messages.value = []
    visited.clear()
    historyLoaded.value = true
  }

  /**
   * Переход делает браузер, а решает сервер: агент лишь называет раздел
   * из разрешённых его собеседнику, и в ответе появляется результат вызова.
   */
  watch(chat.messages, (messages) => {
    for (const target of navigationTargets(messages, visited)) {
      navigateTo(target.path)
    }
  }, { deep: true })

  return {
    messages: chat.messages,
    status: chat.status,
    error: chat.error,
    sendMessage: chat.sendMessage,
    stop: chat.stop,
    historyPending,
    loadHistory,
    reset
  }
}

/** Текст сообщения: части склеиваются, служебные — пропускаются. */
export function textOf(message?: UIMessage): string {
  return (message?.parts ?? [])
    .filter(part => part.type === 'text')
    .map(part => part.text)
    .join('\n')
    .trim()
}

/** Разделы, которые агент открыл в этом ответе и которых ещё не было. */
function navigationTargets(messages: UIMessage[], visited: Set<string>): NavigationTarget[] {
  const targets: NavigationTarget[] = []

  for (const message of messages) {
    for (const part of message.parts ?? []) {
      if (part.type !== NAVIGATION_PART) continue

      const call = part as { toolCallId: string, state: string, output?: unknown }

      if (call.state !== 'output-available' || visited.has(call.toolCallId)) continue

      visited.add(call.toolCallId)

      const target = parseTarget(call.output)

      if (target) targets.push(target)
    }
  }

  return targets
}

/**
 * Инструмент отдаёт строку — JSON с адресом либо человеческий отказ,
 * если раздел закрыт ролью. Отказ разбору не поддаётся, и это нормально:
 * переходить тогда некуда, агент объяснит словами.
 */
export function parseTarget(output: unknown): NavigationTarget | null {
  if (typeof output !== 'string') return null

  try {
    const parsed = JSON.parse(output) as Partial<NavigationTarget>

    return parsed.path?.startsWith('/') ? { path: parsed.path, label: parsed.label ?? '' } : null
  } catch {
    return null
  }
}
