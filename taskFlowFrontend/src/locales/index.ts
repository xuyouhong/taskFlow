import { createI18n } from 'vue-i18n'
import zhCN from './zh-CN'
import en from './en'

const getLanguage = (): string => {
  const appData = localStorage.getItem('app')
  if (appData) {
    try {
      const { language } = JSON.parse(appData)
      if (language) return language
    } catch {
      // ignore
    }
  }
  return 'zh-CN'
}

const i18n = createI18n({
  legacy: false,
  locale: getLanguage(),
  fallbackLocale: 'zh-CN',
  messages: {
    'zh-CN': zhCN,
    en: en,
  },
})

export default i18n
