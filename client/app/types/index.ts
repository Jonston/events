/** Роли площадки — зеркало `App\Enums\UserRole` на сервере. */
export type UserRole = 'admin' | 'master' | 'member'

export interface User {
  id: number
  name: string
  email: string
  phone: string | null
  avatarUrl: string | null
  roles: UserRole[]
  emailVerified: boolean
  createdAt: string
}

/** Постраничная выдача списков API: `{ data, meta }`. */
export interface PaginationMeta {
  page: number
  perPage: number
  total: number
  lastPage: number
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}

/** Параметры запроса списка: общий контракт для всех табличных экранов. */
export interface ListQuery {
  page?: number
  perPage?: number
  search?: string
  sort?: string
  direction?: 'asc' | 'desc'
}

export type ValidationErrors = Record<string, string[]>

/* ── Витрина ─────────────────────────────────────────────────────────── */

/** Средний балл и число отзывов. Null у мастера — оценок ещё нет. */
export interface RatingSummary {
  value: number
  count: number
}

/** Мастер в каталоге. */
export interface MasterCard {
  id: number
  slug: string
  name: string
  headline: string | null
  city: string | null
  photo: string | null
  cover: string | null
  rating: RatingSummary | null
  upcomingEventsCount: number
}

/**
 * Мероприятие в списке. Цена приходит уже отформатированной: одна и та же
 * сумма должна выглядеть одинаково на всех экранах.
 */
export interface EventCard {
  id: number
  slug: string
  title: string
  summary: string | null
  image: string | null
  startsAt: string
  endsAt: string | null
  location: string | null
  price: number
  priceFormatted: string
  masterId: number | null
  masterName: string | null
  masterSlug: string | null
}

export interface Review {
  id: number
  author: string
  rating: number
  body: string | null
  createdAt: string
}

export interface MasterDetail {
  card: MasterCard
  bio: string | null
  events: EventCard[]
  reviews: Review[]
}

/** Пункт программы: время уже собрано сервером в «10:00–13:00». */
export interface ScheduleItem {
  time: string
  title: string
  note: string | null
}

export interface ScheduleDay {
  day: string
  items: ScheduleItem[]
}

export interface EventDetail {
  card: EventCard
  description: string | null
  capacity: number | null
  schedule: ScheduleDay[]
  depositFormatted: string
  remainingFormatted: string
  refundDeadline: string
}

export interface HomeContent {
  masters: MasterCard[]
  events: EventCard[]
}

/** Заявка с формы. */
export interface LeadPayload {
  name: string
  phone: string
  email?: string
  message?: string
  eventId?: number | null
  masterId?: number | null
}

/* ── Кабинет мастера ──────────────────────────────────────────────────── */

/** Пункт программы в форме мероприятия. */
export interface EventSessionForm {
  day: string
  startsAt: string
  endsAt: string | null
  title: string
  note: string | null
}

/**
 * Мероприятие в кабинете. Отличается от витринного: здесь видны черновики,
 * счётчик заявок и сырые значения полей — форма правит числа и даты,
 * а не подписи к ним.
 */
export interface MasterEvent {
  id: number
  slug: string
  title: string
  summary: string | null
  description: string | null
  image: string | null
  startsAt: string
  endsAt: string | null
  location: string | null
  latitude: number | null
  longitude: number | null
  price: number
  priceFormatted: string
  depositRate: number | null
  capacity: number | null
  isPublished: boolean
  leadsCount: number
  sessions: EventSessionForm[]
}

export interface MasterProfile {
  id: number
  slug: string
  name: string
  headline: string | null
  bio: string | null
  city: string | null
  photo: string | null
  cover: string | null
  isActive: boolean
  publicUrl: string
}

export interface MasterOverview {
  hasProfile: boolean
  isActive?: boolean
  publicUrl?: string
  published?: number
  drafts?: number
  newLeads?: number
  totalLeads?: number
  rating?: RatingSummary | null
  nextEvent?: {
    title: string
    slug: string
    startsAt: string
    leadsCount: number
    capacity: number | null
  } | null
}

export interface LeadStatus {
  value: string
  label: string
}

export interface MasterLead {
  id: number
  name: string
  phone: string | null
  email: string | null
  message: string | null
  status: string
  statusLabel: string
  eventTitle: string | null
  eventSlug: string | null
  createdAt: string
}

export interface MasterReview {
  id: number
  author: string
  rating: number
  body: string | null
  status: string
  statusLabel: string
  createdAt: string
}
