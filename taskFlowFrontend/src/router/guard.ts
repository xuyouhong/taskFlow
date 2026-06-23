import type { Router } from 'vue-router'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import { useAuthStore } from '@/stores/auth'
import { usePermissionStore } from '@/stores/permission'
import { useTagsViewStore } from '@/stores/tagsView'
import i18n from '@/locales'

NProgress.configure({ showSpinner: false })

const WHITE_LIST = ['/login', '/403']

export function setupRouterGuard(router: Router) {
  router.beforeEach(async (to, _from, next) => {
    NProgress.start()

    // Set page title — translate i18n keys if applicable
    if (to.meta.title) {
      const rawTitle = to.meta.title as string
      let pageTitle = rawTitle
      if (rawTitle.includes('.')) {
        const t = i18n.global.t
        const translated = t(rawTitle)
        if (translated !== rawTitle) {
          pageTitle = translated
        }
      }
      document.title = `${pageTitle} - ${import.meta.env.VITE_APP_TITLE}`
    } else {
      document.title = import.meta.env.VITE_APP_TITLE
    }

    const authStore = useAuthStore()
    const permissionStore = usePermissionStore()

    // Allow whitelist routes and redirect route (used by TagsView refresh)
    if (WHITE_LIST.includes(to.path) || to.meta.noAuth || to.path.startsWith('/redirect')) {
      if (to.path === '/login' && authStore.isAuthenticated) {
        next('/dashboard')
      } else {
        next()
      }
      return
    }

    // Check authentication
    if (!authStore.isAuthenticated) {
      next(`/login?redirect=${to.path}`)
      return
    }

    // Fetch user info if not loaded or if routes haven't been generated yet (page refresh)
    if (!authStore.userInfo || !permissionStore.isRoutesLoaded) {
      try {
        await authStore.fetchUserInfo()
      } catch (e) {
        console.error('Failed to fetch user info:', e)
        authStore.resetAuth()
        next(`/login?redirect=${to.path}`)
        return
      }
    }

    // Generate dynamic routes if not loaded
    if (!permissionStore.isRoutesLoaded) {
      try {
        await permissionStore.generateRoutes(router)
        // Re-navigate to trigger the new routes
        next({ path: to.fullPath, replace: true })
        return
      } catch (e) {
        console.error('Failed to generate routes:', e)
        authStore.resetAuth()
        permissionStore.clearRoutes(router)
        next(`/login?redirect=${to.path}`)
        return
      }
    }

    // Add to tags view
    if (to.name && to.meta.title && !to.meta.hidden) {
      const tagsViewStore = useTagsViewStore()
      tagsViewStore.addTag(to)
    }

    next()
  })

  router.afterEach(() => {
    NProgress.done()
  })

  // Catch navigation errors to prevent unexpected page refreshes
  router.onError((error, to, _from) => {
    // Ignore common non-critical navigation errors
    const msg = error.message || ''
    if (
      msg.includes('Redirected when going from') ||
      msg.includes('Navigation cancelled') ||
      msg.includes('Navigation aborted') ||
      msg.includes('Avoided redundant navigation')
    ) {
      console.warn('[Router]', msg)
      return
    }
    console.error('[Router] Navigation error:', error, '→ to:', to.path)
    NProgress.done()
  })
}
