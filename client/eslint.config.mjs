// @ts-check
import withNuxt from './.nuxt/eslint.config.mjs'

export default withNuxt({
  rules: {
    // Страницы и layout'ы Nuxt адресуются по имени файла (`layout: 'auth'`),
    // и переименование их в двусложные сломало бы маршрутизацию.
    'vue/multi-word-component-names': 'off'
  }
})
