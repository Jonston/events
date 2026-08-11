import type { NavigationMenuItem } from '@nuxt/ui'
import type { User, UserRole } from '~/types'

interface CabinetSection {
  /** Куда попадает человек этой роли сразу после входа. */
  home: string
  brand: { label: string, icon: string }
  navItems: NavigationMenuItem[]
}

/**
 * Кабинет у ролей разный, а оболочка одна.
 *
 * Здесь же лежит и точка входа каждой роли: форма входа одна на всех,
 * и после неё человека надо развести по его собственному кабинету —
 * администратора в панель площадки, мастера к его мероприятиям,
 * участника к его записям.
 */
const SECTIONS: Record<UserRole, CabinetSection> = {
  admin: {
    home: '/admin',
    brand: { label: 'Админка', icon: 'i-lucide-shield-check' },
    navItems: [
      { label: 'Обзор', icon: 'i-lucide-layout-dashboard', to: '/admin' },
      { label: 'Пользователи', icon: 'i-lucide-users', to: '/admin/users' },
      { label: 'Мастера', icon: 'i-lucide-sparkles', disabled: true },
      { label: 'Мероприятия', icon: 'i-lucide-calendar-days', disabled: true },
      { label: 'Заявки', icon: 'i-lucide-inbox', disabled: true },
      { label: 'Отзывы', icon: 'i-lucide-message-square-quote', disabled: true }
    ]
  },
  master: {
    home: '/master',
    brand: { label: 'Кабинет мастера', icon: 'i-lucide-sparkles' },
    navItems: [
      { label: 'Обзор', icon: 'i-lucide-layout-dashboard', to: '/master' },
      { label: 'Моя страница', icon: 'i-lucide-id-card', to: '/master/profile' },
      { label: 'Мои мероприятия', icon: 'i-lucide-calendar-days', to: '/master/events' },
      { label: 'Заявки ко мне', icon: 'i-lucide-inbox', to: '/master/leads' },
      { label: 'Отзывы', icon: 'i-lucide-message-square-quote', to: '/master/reviews' }
    ]
  },
  member: {
    home: '/cabinet',
    brand: { label: 'Мой кабинет', icon: 'i-lucide-user' },
    navItems: [
      { label: 'Обзор', icon: 'i-lucide-layout-dashboard', to: '/cabinet' },
      { label: 'Мои записи', icon: 'i-lucide-ticket', disabled: true },
      { label: 'Мои отзывы', icon: 'i-lucide-message-square-quote', disabled: true },
      { label: 'Стать мастером', icon: 'i-lucide-sparkles', disabled: true }
    ]
  }
}

/**
 * Старшая роль человека. Администратор, у которого есть и профиль мастера,
 * должен попадать в панель площадки: это его основная работа.
 */
export function primaryRole(user: User | null): UserRole {
  if (!user) return 'member'
  if (user.roles.includes('admin')) return 'admin'
  if (user.roles.includes('master')) return 'master'

  return 'member'
}

/** Адрес кабинета для этого человека. */
export function cabinetHome(user: User | null): string {
  return SECTIONS[primaryRole(user)].home
}

export function useCabinetNav() {
  const { currentUser } = useAuth()

  const section = computed(() => SECTIONS[primaryRole(currentUser.value)])

  const searchGroups = computed(() => [
    {
      id: 'nav',
      label: 'Навигация',
      items: section.value.navItems
        .filter(item => item.to)
        .map(item => ({ label: item.label as string, icon: item.icon, to: item.to }))
    }
  ])

  return {
    home: computed(() => section.value.home),
    brand: computed(() => section.value.brand),
    navItems: computed(() => section.value.navItems),
    searchGroups
  }
}
