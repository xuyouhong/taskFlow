import { useAuthStore } from '@/stores/auth'

export function checkPermission(permissions: string[]): boolean {
  const authStore = useAuthStore()
  if (!authStore.userInfo) return false

  const user = authStore.userInfo as any

  // Method 1: top-level permissions (string[] from backend)
  if (user.permissions && Array.isArray(user.permissions)) {
    const matched = permissions.some((p) => {
      return user.permissions.some((up: any) => {
        if (typeof up === 'string') return up === p
        return up?.slug === p || up?.name === p
      })
    })
    if (matched) return true
  }

  // Method 2: roles[].permissions[] (object array)
  const rolePermissions = user.roles?.flatMap((r: any) => r.permissions || []) || []
  return permissions.some((p) =>
    rolePermissions.some((up: any) => {
      if (typeof up === 'string') return up === p
      return up?.slug === p || up?.name === p
    })
  )
}

export function checkRole(roles: string[]): boolean {
  const authStore = useAuthStore()
  if (!authStore.userInfo) return false
  return roles.some((r) => authStore.roles.includes(r))
}

export function isSuperAdmin(): boolean {
  return checkRole(['super-admin'])
}

export function isAdmin(): boolean {
  return checkRole(['admin', 'super-admin'])
}
