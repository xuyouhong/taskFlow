import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAppStore = defineStore('app', () => {
  const sidebarCollapsed = ref(false)
  const theme = ref<'light' | 'dark'>('light')
  const language = ref<'zh-CN' | 'en'>('zh-CN')

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    applyTheme()
  }

  function setTheme(newTheme: 'light' | 'dark') {
    theme.value = newTheme
    applyTheme()
  }

  function applyTheme() {
    document.documentElement.setAttribute('data-theme', theme.value)
    document.body.className = theme.value === 'dark' ? 'dark-mode' : 'light-mode'
  }

  function setLanguage(lang: 'zh-CN' | 'en') {
    language.value = lang
  }

  function initTheme() {
    applyTheme()
  }

  return {
    sidebarCollapsed,
    theme,
    language,
    toggleSidebar,
    toggleTheme,
    setTheme,
    setLanguage,
    initTheme,
  }
}, {
  persist: {
    pick: ['sidebarCollapsed', 'theme', 'language'],
    storage: localStorage,
    key: 'app',
  },
})
