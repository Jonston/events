import type { NuxtApp } from '#app'

const AUTH_TOKEN_COOKIE = 'auth.token'

/**
 * Токен хранится в cookie, а не в localStorage: без него серверный рендер
 * не знает, кто пришёл, и каждая страница админки сначала мигала бы
 * гостевым состоянием.
 */
const tokenStorage = {
  async get(app: NuxtApp) {
    return app.runWithContext(() =>
      useCookie<string | undefined>(AUTH_TOKEN_COOKIE, { readonly: true }).value ?? undefined
    )
  },
  async set(app: NuxtApp, token?: string) {
    app.runWithContext(() => {
      const cookie = useCookie<string | undefined>(AUTH_TOKEN_COOKIE, {
        maxAge: token ? 60 * 60 * 24 * 365 : -1,
        path: '/',
        sameSite: 'lax'
      })
      cookie.value = token
    })
  }
}

export default defineNuxtPlugin(() => {
  useSanctumTokenStorage(tokenStorage)
})
