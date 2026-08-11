import type { UserRole } from '~/types'

/**
 * Пускает в раздел только свою роль.
 *
 * Роль берётся из `definePageMeta({ role: 'master' })`. Авторизацию проверяет
 * `sanctum:auth`, но вошедший участник — тоже авторизованный человек, и без
 * этой проверки он дошёл бы до чужого кабинета и упёрся в 403 от API.
 * Разворачиваем его на входе, а не на пустом экране.
 */
export default defineNuxtRouteMiddleware((to) => {
  const required = to.meta.role as UserRole | undefined

  if (!required) return

  const { currentUser, hasRole } = useAuth()

  // Пока пользователь не загружен, решать нечего: сработает `sanctum:auth`.
  if (!currentUser.value) return

  if (!hasRole(required)) {
    return navigateTo('/auth/forbidden')
  }
})
