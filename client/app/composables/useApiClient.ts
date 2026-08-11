import type { ListQuery } from '~/types'

/**
 * HTTP-клиент API с уже подставленным токеном Sanctum.
 *
 * Экраны зовут `api('/api/admin/users')` — без базового URL и без ручной
 * сборки заголовка авторизации, поэтому адрес сервера не расходится по коду.
 */
export function useApiClient() {
  const client = useSanctumClient()
  const baseUrl = useRuntimeConfig().public.apiUrl as string

  return <T>(path: string, options: Record<string, unknown> = {}): Promise<T> =>
    client<T>(`${baseUrl}${path}`, options)
}

/**
 * Приводит параметры списка к query-строке, выбрасывая пустые значения:
 * `?search=` на сервере ничего не фильтрует, но делает URL разным
 * при одинаковом результате.
 */
export function toListQuery(query: ListQuery): Record<string, string | number> {
  return Object.fromEntries(
    Object.entries(query).filter(([, value]) => value !== undefined && value !== null && value !== '')
  ) as Record<string, string | number>
}
