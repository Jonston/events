import type { ListQuery, Paginated, User } from '~/types'

/**
 * Единственное место, которое знает адреса пользовательских конечных
 * точек API.
 */
export function useUsersApi() {
  const api = useApiClient()

  const fetchUsers = (query: ListQuery = {}): Promise<Paginated<User>> =>
    api<Paginated<User>>('/api/admin/users', { query: toListQuery(query) })

  const fetchUser = (id: number): Promise<User> =>
    api<User>(`/api/admin/users/${id}`)

  return { fetchUsers, fetchUser }
}
