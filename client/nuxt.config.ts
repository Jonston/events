export default defineNuxtConfig({
  modules: [
    '@nuxt/eslint',
    '@nuxt/ui',
    '@nuxt/fonts',
    '@pinia/nuxt',
    '@vueuse/nuxt',
    'nuxt-auth-sanctum'
  ],

  devtools: {
    enabled: false
  },

  app: {
    head: {
      titleTemplate: '%s — Vibe Space',
      title: 'Мастера и мероприятия',
      htmlAttrs: { lang: 'ru' }
    }
  },

  css: ['~/assets/css/main.css'],

  ui: {
    theme: {
      // `info` заведён ради статусов заявки: без него синему бейджу
      // неоткуда взяться — набор псевдонимов задаётся здесь, а не в app.config.
      colors: ['primary', 'secondary', 'success', 'info', 'warning', 'error', 'neutral']
    }
  },

  runtimeConfig: {
    public: {
      apiUrl: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8001',
      appName: process.env.NUXT_PUBLIC_APP_NAME || 'Админка Events',
      // Имя площадки на витрине. Отдельно от appName: в шапке сайта
      // «Админка Events» выглядело бы как чужая вкладка.
      siteName: process.env.NUXT_PUBLIC_SITE_NAME || 'Vibe Space',
      // Селект с готовыми учётками на экране входа. По умолчанию только
      // в разработке: на боевой площадке такой список означал бы, что
      // зайти под администратором может кто угодно.
      demoLogin: process.env.NUXT_PUBLIC_DEMO_LOGIN === 'true' || process.env.NODE_ENV !== 'production',
      // Ключ MapTiler: тайлы карты и поиск адреса в кабинете мастера.
      // Без него форма не ломается — адрес вписывается руками,
      // а карта честно сообщает, что не настроена.
      maptilerKey: process.env.NUXT_PUBLIC_MAPTILER_KEY || ''
    }
  },

  compatibilityDate: '2025-01-15',

  eslint: {
    config: {
      stylistic: {
        commaDangle: 'never',
        braceStyle: '1tbs'
      }
    }
  },

  /**
   * Шрифты те же, что на витрине: админка и публичная часть — одна площадка,
   * и разнобой гарнитур читался бы как два разных продукта.
   *
   * `subsets` обязателен: по умолчанию тянется только latin, и кириллица
   * молча уехала бы на системный запасной шрифт. Файлы модуль скачивает
   * на сборке и отдаёт с нашего домена — запрос к чужому CDN тут лишний.
   */
  fonts: {
    defaults: {
      subsets: ['latin', 'cyrillic']
    },
    families: [
      { name: 'Roboto', provider: 'google', weights: [300, 400, 500, 700] },
      { name: 'Roboto Condensed', provider: 'google', weights: [400, 500, 700] }
    ]
  },

  /**
   * Иконки вшиваются в бандл, а не подгружаются по запросу.
   *
   * По умолчанию модуль берёт их с адреса `/api/_nuxt_icon` — относительного,
   * а серверному рендеру такой некуда адресовать: он сыпал «failed to load
   * icon» и отдавал разметку вообще без иконок, так что они появлялись только
   * после гидрации. `scan` собирает те, что реально встречаются в коде,
   * и регистрирует их до первой отрисовки — и на сервере, и в браузере.
   *
   * `globInclude` перечислен вручную: по умолчанию сканируются только `.vue`,
   * а меню кабинета задано в `composables/useCabinetNav.ts` — его иконки
   * в сборку не попадали.
   */
  icon: {
    clientBundle: {
      scan: {
        globInclude: ['app/**/*.{vue,ts}']
      },
      sizeLimitKb: 512
    }
  },

  /**
   * Авторизация токеном, а не сессионной cookie: клиент живёт на своём
   * origin и обращается к Laravel как сторонний потребитель API.
   */
  sanctum: {
    baseUrl: process.env.NUXT_PUBLIC_API_URL || 'http://localhost:8001',
    mode: 'token',
    endpoints: {
      login: '/api/login',
      logout: '/api/logout',
      user: '/api/user'
    },
    redirect: {
      onAuthOnly: '/auth/login',
      // Куда идти после входа, решает сам экран входа: кабинет у ролей
      // разный, и адрес известен только после того, как стало известно,
      // кто вошёл. `false` выключает фиксированный редирект модуля.
      onLogin: false,
      onGuestOnly: false,
      onLogout: '/',
      // Гость, шедший на закрытую страницу, после входа возвращается именно
      // туда, а не на общий вход в кабинет.
      keepRequestedRoute: true
    }
  }
})
