import type { LeadStatus, MasterEvent, MasterLead, MasterOverview, MasterProfile, MasterReview } from '~/types'

/**
 * Кабинет мастера.
 *
 * Формы уходят как `FormData`: и у витрины, и у мероприятия есть картинки,
 * а класть их рядом с JSON нечем. Правка тоже POST — `multipart` в `PUT`
 * браузер не отправляет.
 */
export function useMasterApi() {
  const api = useApiClient()

  const fetchOverview = (): Promise<MasterOverview> =>
    api<MasterOverview>('/api/master/overview')

  const fetchProfile = (): Promise<MasterProfile> =>
    api<MasterProfile>('/api/master/profile')

  const saveProfile = (body: FormData): Promise<MasterProfile> =>
    api<MasterProfile>('/api/master/profile', { method: 'POST', body })

  const fetchEvents = (): Promise<{ data: MasterEvent[] }> =>
    api<{ data: MasterEvent[] }>('/api/master/events')

  const fetchEvent = (id: number): Promise<MasterEvent> =>
    api<MasterEvent>(`/api/master/events/${id}`)

  const createEvent = (body: FormData): Promise<MasterEvent> =>
    api<MasterEvent>('/api/master/events', { method: 'POST', body })

  const updateEvent = (id: number, body: FormData): Promise<MasterEvent> =>
    api<MasterEvent>(`/api/master/events/${id}`, { method: 'POST', body })

  const deleteEvent = (id: number): Promise<{ message: string }> =>
    api<{ message: string }>(`/api/master/events/${id}`, { method: 'DELETE' })

  const fetchLeads = (): Promise<{ data: MasterLead[], statuses: LeadStatus[] }> =>
    api<{ data: MasterLead[], statuses: LeadStatus[] }>('/api/master/leads')

  const setLeadStatus = (id: number, status: string): Promise<{ status: string, statusLabel: string }> =>
    api<{ status: string, statusLabel: string }>(`/api/master/leads/${id}/status`, {
      method: 'PATCH',
      body: { status }
    })

  const fetchReviews = (): Promise<{ data: MasterReview[] }> =>
    api<{ data: MasterReview[] }>('/api/master/reviews')

  return {
    fetchOverview,
    fetchProfile,
    saveProfile,
    fetchEvents,
    fetchEvent,
    createEvent,
    updateEvent,
    deleteEvent,
    fetchLeads,
    setLeadStatus,
    fetchReviews
  }
}

/**
 * Собирает `FormData` из обычного объекта.
 *
 * Пустые значения выбрасываются: `FormData` умеет только строки, и `null`
 * доехал бы до сервера строкой «null», пройдя валидацию `nullable`
 * как заполненное поле. Вложенные списки раскладываются в `sessions[0][title]` —
 * такую форму Laravel разбирает сам.
 */
export function toFormData(payload: Record<string, unknown>, form = new FormData(), prefix = ''): FormData {
  for (const [key, value] of Object.entries(payload)) {
    const field = prefix ? `${prefix}[${key}]` : key

    if (value === null || value === undefined || value === '') continue

    if (value instanceof File) {
      form.append(field, value)
    } else if (Array.isArray(value)) {
      value.forEach((item, index) => {
        if (item !== null && typeof item === 'object') {
          toFormData(item as Record<string, unknown>, form, `${field}[${index}]`)
        } else {
          form.append(`${field}[${index}]`, String(item))
        }
      })
    } else if (typeof value === 'object') {
      toFormData(value as Record<string, unknown>, form, field)
    } else if (typeof value === 'boolean') {
      // Laravel читает как булево только «1» и «0»: строка «false»
      // для правила `boolean` — истина.
      form.append(field, value ? '1' : '0')
    } else {
      form.append(field, String(value))
    }
  }

  return form
}
