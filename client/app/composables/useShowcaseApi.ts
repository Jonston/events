import type { EventCard, EventDetail, HomeContent, LeadPayload, MasterCard, MasterDetail } from '~/types'

/**
 * Витрина: каталог мастеров, мероприятия и отправка заявки.
 *
 * Каталог читается обычным `$fetch`: он открыт гостю и не должен переставать
 * работать оттого, что человек не вошёл. А вот заявка уходит клиентом
 * с токеном — если человек вошёл, она должна остаться привязанной
 * к его аккаунту, иначе своих записей он потом не найдёт.
 */
export function useShowcaseApi() {
  const baseUrl = useRuntimeConfig().public.apiUrl as string
  const client = useSanctumClient()

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
    client<{ id: number, message: string }>(`${baseUrl}/api/leads`, {
      method: 'POST',
      body: payload
    })

  return { fetchHome, fetchMasters, fetchMaster, fetchEvents, fetchEvent, sendLead }
}
