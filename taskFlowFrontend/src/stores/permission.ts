import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Router, RouteRecordRaw } from 'vue-router'
import { getUserMenus, getMenusTree } from '@/api/menu'
import { getUserInfo } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import type { Menu, Permission } from '@/types/api'

// Use import.meta.glob for dynamic component resolution
const viewModules = import.meta.glob('@/views/**/*.vue')

function loadViewComponent(componentPath: string) {
  const normalized = componentPath.replace(/^\//, '').replace(/\.vue$/, '')
  const key = `/src/views/${normalized}.vue`
  if (viewModules[key]) {
    return viewModules[key]
  }
  const keyWithIndex = `/src/views/${normalized}/index.vue`
  if (viewModules[keyWithIndex]) {
    return viewModules[keyWithIndex]
  }
  console.warn(`Component not found: ${componentPath} (tried ${key} and ${keyWithIndex})`)
  return () => import('@/views/error/404.vue')
}

function transformMenuToRoutes(menus: Menu[]): RouteRecordRaw[] {
  if (!Array.isArray(menus)) return []
  const routes: RouteRecordRaw[] = []

  for (const menu of menus) {
    if (menu.status === 0 || menu.type === 3) continue

    // Skip menus whose component is 'Layout' — these are layout wrappers,
    // not view components. The actual page is handled by static routes.
    // But still process their children as top-level routes.
    if (menu.component === 'Layout') {
      if (menu.children && menu.children.length > 0) {
        const childRoutes = transformMenuToRoutes(menu.children)
        routes.push(...childRoutes)
      }
      continue
    }

    const route: RouteRecordRaw = {
      path: menu.path || '',
      name: `menu-${menu.hash_id}`,
      meta: {
        title: menu.name,
        icon: menu.icon,
        keepAlive: menu.keep_alive === 1,
        hidden: false,
        type: menu.type,
      },
      component: undefined,
      children: [],
    }

    if (menu.type === 2 && menu.component) {
      // Page menu — load the actual view component
      route.component = loadViewComponent(menu.component)
    }

    if (menu.children && menu.children.length > 0) {
      const childRoutes = transformMenuToRoutes(menu.children)
      if (childRoutes.length > 0) {
        route.children = childRoutes
        if (menu.type === 1) {
          // Directory menu: use pass-through component for router-view chain
          route.component = { template: '<router-view />' } as any
          // Fix redirect: use child's absolute path directly, or concatenate if relative
          const firstChildPath = childRoutes[0].path
          if (firstChildPath.startsWith('/')) {
            route.redirect = firstChildPath
          } else {
            route.redirect = `${menu.path}/${firstChildPath}`.replace(/\/+/g, '/')
          }
        }
      }
    }

    // Fallback: if directory has no children, still give it pass-through
    if (menu.type === 1 && !route.component) {
      route.component = { template: '<router-view />' } as any
    }

    // Don't add routes without a component (prevents blank pages)
    if (!route.component) {
      console.warn(`[Router] Skipping menu "${menu.name}" (${menu.path}): no component resolved`)
      continue
    }

    routes.push(route)
  }

  return routes
}

export const usePermissionStore = defineStore('permission', () => {
  const menuList = ref<Menu[]>([])
  const allMenuList = ref<Menu[]>([])
  const permissionList = ref<Permission[]>([])
  const permissionsLoaded = ref(false)
  const isRoutesLoaded = ref(false)

  // Super admin check — backend config defines super_admin_role as 'super-admin'
  // NOTE: 'admin' is the regular 管理员 role, NOT super admin — do NOT include it here
  const adminRoles = ['super-admin', 'super_admin']

  const isSuperAdmin = computed(() => {
    const authStore = useAuthStore()

    // Check userInfo.roles (object array with slug/name fields)
    if (authStore.userInfo && Array.isArray(authStore.userInfo.roles)) {
      const hasAdminRole = authStore.userInfo.roles.some((role: any) => {
        if (typeof role === 'string') {
          return adminRoles.includes(role.toLowerCase())
        }
        if (typeof role === 'object') {
          return adminRoles.includes((role.slug || role.name || '').toLowerCase())
        }
        return false
      })
      if (hasAdminRole) return true
    }

    // Fallback: check flat roles array
    if (Array.isArray(authStore.roles)) {
      return authStore.roles.some((role: any) => {
        if (typeof role === 'string') {
          return adminRoles.includes(role.toLowerCase())
        }
        if (typeof role === 'object') {
          return adminRoles.includes((role.slug || role.name || '').toLowerCase())
        }
        return false
      })
    }

    return false
  })

  const flatMenuList = computed(() => {
    const result: Menu[] = []
    function flatten(menus: Menu[]) {
      if (!Array.isArray(menus)) return
      for (const menu of menus) {
        result.push(menu)
        if (menu.children?.length) {
          flatten(menu.children)
        }
      }
    }
    flatten(menuList.value)
    return result
  })

  const permissionMenus = computed(() => {
    return filterMenusByPermission(menuList.value)
  })

  function filterMenusByPermission(menus: Menu[]): Menu[] {
    if (!Array.isArray(menus)) return []
    return menus.filter((menu) => {
      if (menu.type === 1) {
        if (menu.children && menu.children.length > 0) {
          const filteredChildren = filterMenusByPermission(menu.children)
          return filteredChildren.length > 0
        }
        return false
      }
      if (menu.type === 3) return false
      return menu.status === 1
    })
  }

  async function generateRoutes(router: Router) {
    // All three APIs return unwrapped data (interceptor returns data.data)
    const [userMenus, fullTree, userData] = await Promise.all([
      getUserMenus(),
      getMenusTree(),
      getUserInfo(),
    ])

    // userMenus: Menu[] (tree structure)
    const menus = Array.isArray(userMenus) ? userMenus : (userMenus as any)?.list || []
    menuList.value = menus

    // fullTree: Menu[] (tree structure)
    const tree = Array.isArray(fullTree) ? fullTree : (fullTree as any)?.list || []
    allMenuList.value = tree

    // Extract permissions from user roles + top-level user.permissions
    const userPerms: Permission[] = []
    const user = userData as any

    // Sync fresh roles and userInfo to auth store (fixes isSuperAdmin check)
    const authStore = useAuthStore()
    if (user) {
      authStore.userInfo = user
      const freshRoles = user.roles?.map((r: any) => r.slug) || []
      if (freshRoles.length > 0) {
        authStore.roles = freshRoles
      }
    }

    // Method 1: Extract from roles[].permissions[]
    if (user?.roles) {
      for (const role of user.roles) {
        if (role.permissions) {
          for (const perm of role.permissions) {
            if (!userPerms.find((p) => p.hash_id === perm.hash_id)) {
              userPerms.push(perm)
            }
          }
        }
      }
    }

    // Method 2: Fallback — top-level user.permissions (if any)
    if (user?.permissions && Array.isArray(user.permissions)) {
      for (const perm of user.permissions) {
        if (typeof perm === 'string') {
          // String permission — wrap as object for consistent matching
          if (!userPerms.find((p) => p.slug === perm)) {
            userPerms.push({ slug: perm, name: perm } as any)
          }
        } else if (perm && typeof perm === 'object') {
          if (!userPerms.find((p) => p.hash_id === perm.hash_id)) {
            userPerms.push(perm)
          }
        }
      }
    }

    permissionList.value = userPerms
    permissionsLoaded.value = true

    // Persist to localStorage for page refresh
    try {
      const authData = localStorage.getItem('auth')
      if (authData) {
        const parsed = JSON.parse(authData)
        parsed.permissionList = userPerms
        localStorage.setItem('auth', JSON.stringify(parsed))
      }
    } catch { /* ignore */ }

    // Transform menus to routes and add to router
    const routes = transformMenuToRoutes(menuList.value)

    const layoutRoute = router.getRoutes().find((r) => r.name === 'Layout')

    if (layoutRoute) {
      for (const route of routes) {
        router.addRoute('Layout', route)
      }
    }

    // Add 404 catch-all AFTER dynamic routes
    router.addRoute({
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      component: () => import('@/views/error/404.vue'),
      meta: { hidden: true, noAuth: true },
    })

    isRoutesLoaded.value = true
  }

  function clearRoutes(router?: Router) {
    menuList.value = []
    allMenuList.value = []
    permissionList.value = []
    permissionsLoaded.value = false
    isRoutesLoaded.value = false
    // Remove dynamically added routes
    if (router) {
      const routes = router.getRoutes()
      for (const route of routes) {
        if (route.name && typeof route.name === 'string' && route.name.startsWith('menu-')) {
          router.removeRoute(route.name)
        }
      }
      router.removeRoute('NotFound')
    }
  }

  function hasPermission(slug: string): boolean {
    return permissionList.value.some((p) => p.slug === slug || p.name === slug)
  }

  // Restore permissionList from localStorage on page refresh
  function initPermission() {
    try {
      const authData = localStorage.getItem('auth')
      if (authData) {
        const parsed = JSON.parse(authData)
        if (parsed.permissionList && Array.isArray(parsed.permissionList)) {
          permissionList.value = parsed.permissionList
          permissionsLoaded.value = true
        }
      }
    } catch { /* ignore */ }
  }

  return {
    menuList,
    allMenuList,
    permissionList,
    permissionsLoaded,
    isRoutesLoaded,
    isSuperAdmin,
    flatMenuList,
    permissionMenus,
    generateRoutes,
    clearRoutes,
    hasPermission,
    initPermission,
  }
})
