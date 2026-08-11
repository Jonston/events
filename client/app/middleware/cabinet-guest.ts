/**
 * Форма входа для того, кто уже вошёл.
 *
 * Штатный `sanctum:guest` уводит на один фиксированный адрес, а кабинет
 * у ролей разный — поэтому разводим сами, тем же правилом, что и после входа.
 */
export default defineNuxtRouteMiddleware(() => {
  const { currentUser } = useAuth()

  if (currentUser.value) {
    return navigateTo(cabinetHome(currentUser.value))
  }
})
