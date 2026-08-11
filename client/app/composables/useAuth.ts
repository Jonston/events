import type { User, UserRole } from '~/types'

/**
 * Единая точка входа в авторизацию: оборачивает `nuxt-auth-sanctum`,
 * чтобы экраны не знали ни про имя модуля, ни про форму его API.
 */
export function useAuth() {
  const { user, isAuthenticated, init, login, logout, refreshIdentity } = useSanctumAuth<User>()

  const currentUser = computed(() => user.value as User | null)

  const hasRole = (role: UserRole): boolean =>
    currentUser.value?.roles.includes(role) ?? false

  return {
    currentUser,
    isAuthenticated,
    hasRole,
    initialize: init,
    login,
    logout,
    refreshIdentity
  }
}
