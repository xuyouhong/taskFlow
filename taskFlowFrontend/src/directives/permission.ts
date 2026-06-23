import type { Directive } from 'vue'
import { watch } from 'vue'
import { usePermissionStore } from '@/stores/permission'
import { useAuthStore } from '@/stores/auth'

// Track all elements using v-permission for reactive re-checking
const permissionElements: Array<{
  el: HTMLElement
  binding: any
}> = []

function checkPermission(el: HTMLElement, binding: any) {
  const { value } = binding
  if (!value || !Array.isArray(value) || value.length === 0) {
    el.style.display = ''
    return
  }

  const permissionStore = usePermissionStore()

  // Super admin has all permissions — always visible
  if (permissionStore.isSuperAdmin) {
    el.style.display = ''
    return
  }

  // Permissions not yet loaded — hide element, will re-check via watch
  if (!permissionStore.permissionsLoaded) {
    el.style.display = 'none'
    return
  }

  const permissions = permissionStore.permissionList || []
  const permissionsArray = Array.isArray(permissions) ? permissions : []

  // Permissions loaded but user has no matching permissions — hide
  if (permissionsArray.length === 0) {
    el.style.display = 'none'
    return
  }

  const requiredPermissions = value as string[]

  const hasPerm = permissionsArray.some((permission: any) => {
    if (typeof permission === 'string') {
      return requiredPermissions.includes(permission)
    }
    if (!permission || typeof permission !== 'object') {
      return false
    }
    return (
      requiredPermissions.includes(permission.slug) ||
      requiredPermissions.includes(permission.name)
    )
  })

  el.style.display = hasPerm ? '' : 'none'
}

export const permissionDirective: Directive = {
  mounted(el: HTMLElement, binding) {
    // Track this element
    permissionElements.push({ el, binding })

    // Initial permission check
    checkPermission(el, binding)

    // Watch permissionList for changes — re-check ALL tracked elements
    const permissionStore = usePermissionStore()
    const unwatch = watch(
      () => permissionStore.permissionList,
      () => {
        permissionElements.forEach(({ el: trackedEl, binding: trackedBinding }) => {
          checkPermission(trackedEl, trackedBinding)
        })
      },
      { deep: true },
    )

    // Watch permissionsLoaded flag — re-check when permissions finish loading
    const unwatchLoaded = watch(
      () => permissionStore.permissionsLoaded,
      () => {
        permissionElements.forEach(({ el: trackedEl, binding: trackedBinding }) => {
          checkPermission(trackedEl, trackedBinding)
        })
      },
    )

    // Also watch auth roles — isSuperAdmin depends on roles
    const authStore = useAuthStore()
    const unwatchRoles = watch(
      () => authStore.roles,
      () => {
        permissionElements.forEach(({ el: trackedEl, binding: trackedBinding }) => {
          checkPermission(trackedEl, trackedBinding)
        })
      },
      { deep: true },
    )

    // Store unwatches for cleanup
    ;(el as any)._unwatchPermission = unwatch
    ;(el as any)._unwatchLoaded = unwatchLoaded
    ;(el as any)._unwatchRoles = unwatchRoles
  },

  updated(el: HTMLElement, binding) {
    // Update tracked binding
    const index = permissionElements.findIndex((item) => item.el === el)
    if (index !== -1) {
      permissionElements[index].binding = binding
    }

    // Re-check permission
    checkPermission(el, binding)
  },

  unmounted(el: HTMLElement) {
    // Remove from tracking
    const index = permissionElements.findIndex((item) => item.el === el)
    if (index !== -1) {
      permissionElements.splice(index, 1)
    }

    // Cleanup watchers
    if ((el as any)._unwatchPermission) {
      (el as any)._unwatchPermission()
    }
    if ((el as any)._unwatchLoaded) {
      (el as any)._unwatchLoaded()
    }
    if ((el as any)._unwatchRoles) {
      (el as any)._unwatchRoles()
    }
  },
}
