import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import App from './App.vue'
import router from './router'
import { setupRouterGuard } from './router/guard'
import { setupDirectives } from './directives'
import { useAuthStore } from './stores/auth'
import { usePermissionStore } from './stores/permission'
import i18n from './locales'
import './styles/index.scss'

const app = createApp(App)

// Pinia
const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
app.use(pinia)

// Element Plus
app.use(ElementPlus)

// Register all Element Plus icons
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

// i18n
app.use(i18n)

// Router
app.use(router)
setupRouterGuard(router)

// Directives
setupDirectives(app)

// Initialize stores from localStorage BEFORE mount (so route guard has data)
const authStore = useAuthStore()
const permissionStore = usePermissionStore()
authStore.initAuth()
permissionStore.initPermission()

app.mount('#app')
