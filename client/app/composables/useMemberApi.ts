import type { User } from '~/types'

/** Свои данные участника: имя и телефоны. */
export function useMemberApi() {
  const api = useApiClient()

  const fetchProfile = (): Promise<User> =>
    api<User>('/api/cabinet/profile')

  const saveProfile = (body: { name: string, phones: Array<{ phone: string, isPrimary: boolean }> }): Promise<User> =>
    api<User>('/api/cabinet/profile', { method: 'PUT', body })

  return { fetchProfile, saveProfile }
}
