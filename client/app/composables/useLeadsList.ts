import type { LocationQueryRaw } from 'vue-router'
import type { LeadQuery, MasterLead } from '~/types'

/**
 * Состояние экрана «Заявки»: страница, поиск, фильтры и сортировка живут
 * в URL, поэтому отфильтрованный список можно переслать себе же в заметку,
 * а «назад» возвращает тот же экран, а не первую страницу.
 */
export function useLeadsList() {
  const { fetchLeads } = useMasterApi()
  const route = useRoute()
  const router = useRouter()

  const query = computed<Required<Pick<LeadQuery, 'page' | 'perPage' | 'search' | 'sort' | 'direction' | 'status' | 'eventId'>>>(() => ({
    page: Number(route.query.page ?? 1),
    perPage: Number(route.query.perPage ?? 15),
    search: String(route.query.search ?? ''),
    status: String(route.query.status ?? ''),
    eventId: String(route.query.eventId ?? ''),
    sort: String(route.query.sort ?? 'created_at'),
    direction: route.query.direction === 'asc' ? 'asc' : 'desc'
  }))

  const { data, status: state, refresh } = useAsyncData(
    'master-leads',
    () => fetchLeads(query.value),
    { watch: [query] }
  )

  const leads = computed<MasterLead[]>(() => data.value?.data ?? [])
  const total = computed(() => data.value?.meta.total ?? 0)
  const statuses = computed(() => data.value?.statuses ?? [])
  const events = computed(() => data.value?.events ?? [])
  const pending = computed(() => state.value === 'pending')

  /** Меняет часть запроса; всё, кроме самой страницы, сбрасывает её на первую. */
  function setQuery(patch: LeadQuery): void {
    const merged: Record<string, unknown> = { ...route.query, ...patch }

    if (!('page' in patch)) {
      merged.page = undefined
    }

    const next = Object.fromEntries(
      Object.entries(merged).filter(([, value]) => value !== '' && value !== undefined && value !== null)
    )

    router.push({ query: next as LocationQueryRaw })
  }

  function toggleSort(column: string): void {
    setQuery({
      sort: column,
      direction: query.value.sort === column && query.value.direction === 'asc' ? 'desc' : 'asc'
    })
  }

  /**
   * Заменяет заявку в уже загруженном списке.
   *
   * Иначе после смены статуса пришлось бы перезапрашивать всю страницу —
   * и она уехала бы под курсором, если фильтр по статусу включён.
   */
  function replace(lead: MasterLead): void {
    if (!data.value) return

    data.value = {
      ...data.value,
      data: data.value.data.map(item => item.id === lead.id ? lead : item)
    }
  }

  return { leads, total, statuses, events, query, pending, setQuery, toggleSort, refresh, replace }
}
