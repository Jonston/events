import type { EventCard, EventDetail, HomeContent, LeadPayload, MasterCard, MasterDetail } from '~/types'

/**
 * Витрина: каталог мастеров, мероприятия и отправка заявки.
 *
 * Все эти адреса открыты гостю, поэтому здесь обычный `$fetch`, а не клиент
 * с токеном: каталог не должен переставать работать оттого, что человек
 * не вошёл.
 */
export function useShowcaseApi() {
  const baseUrl = useRuntimeConfig().public.apiUrl as string

  const fetchHome = (): Promise<HomeContent> =>
    $fetch<HomeContent>(`${baseUrl}/api/home`)

  const fetchMasters = (): Promise<{ data: MasterCard[] }> =>
    $fetch<{ data: MasterCard[] }>(`${baseUrl}/api/masters`)

  const fetchMaster = (slug: string): Promise<MasterDetail> =>
    $fetch<MasterDetail>(`${baseUrl}/api/masters/${slug}`)

  const fetchEvents = (): Promise<{ data: EventCard[] }> =>
    $fetch<{ data: EventCard[] }>(`${baseUrl}/api/events`)

  const fetchEvent = (slug: string): Promise<EventDetail> =>
    $fetch<EventDetail>(`${baseUrl}/api/events/${slug}`)

  const sendLead = (payload: LeadPayload): Promise<{ id: number, message: string }> =>
    $fetch<{ id: number, message: string }>(`${baseUrl}/api/leads`, {
      method: 'POST',
      body: payload
    })

  return { fetchHome, fetchMasters, fetchMaster, fetchEvents, fetchEvent, sendLead }
}
