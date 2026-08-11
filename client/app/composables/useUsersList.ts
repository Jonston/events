import type { LocationQueryRaw } from 'vue-router'
import type { ListQuery, User } from '~/types'

/**
 * Состояние экрана «Пользователи»: страница, поиск и сортировка живут
 * в URL, поэтому ссылку на отфильтрованный список можно переслать,
 * а «назад» в браузере возвращает тот же экран, а не первую страницу.
 */
export function useUsersList() {
  const { fetchUsers } = useUsersApi()
  const route = useRoute()
  const router = useRouter()

  const query = computed<Required<Pick<ListQuery, 'page' | 'perPage' | 'search' | 'sort' | 'direction'>>>(() => ({
    page: Number(route.query.page ?? 1),
    perPage: Number(route.query.perPage ?? 15),
    search: String(route.query.search ?? ''),
    sort: String(route.query.sort ?? 'created_at'),
    direction: route.query.direction === 'asc' ? 'asc' : 'desc'
  }))

  const { data, status, error, refresh } = useAsyncData(
    'admin-users',
    () => fetchUsers(query.value),
    { watch: [query] }
  )

  const users = computed<User[]>(() => data.value?.data ?? [])
  const total = computed(() => data.value?.meta.total ?? 0)
  const pending = computed(() => status.value === 'pending')

  /** Меняет часть запроса; всё, кроме самой страницы, сбрасывает её на первую. */
  function setQuery(patch: ListQuery): void {
    const merged: Record<string, unknown> = { ...route.query, ...patch }

    // Изменился фильтр, а не страница — значит, отсчёт начинается заново:
    // третьей страницы результатов нового поиска может и не быть.
    if (!('page' in patch)) {
      merged.page = undefined
    }

    const next = Object.fromEntries(
      Object.entries(merged).filter(([, value]) => value !== '' && value !== undefined && value !== null)
    )

    router.push({ query: next as LocationQueryRaw })
  }

  /** Клик по заголовку колонки: тот же столбец переворачивает порядок. */
  function toggleSort(column: string): void {
    setQuery({
      sort: column,
      direction: query.value.sort === column && query.value.direction === 'asc' ? 'desc' : 'asc'
    })
  }

  return { users, total, query, pending, error, refresh, setQuery, toggleSort }
}
