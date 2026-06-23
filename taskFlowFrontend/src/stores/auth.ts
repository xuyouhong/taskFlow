import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as loginApi, logout as logoutApi, getUserInfo as getUserInfoApi } from '@/api/auth'
import type { User, LoginParams } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>('')
  const userInfo = ref<User | null>(null)
  const roles = ref<string[]>([])

  const isAuthenticated = computed(() => !!token.value)

  async function login(params: LoginParams) {
    // loginApi returns raw envelope: { data: { access_token, user }, message, status_code }
    const res = await loginApi(params) as any
    const loginData = res.data || res
    token.value = loginData.access_token
    userInfo.value = loginData.user
    roles.value = loginData.user?.roles?.map((r: any) => r.slug) || []
    saveToStorage()
    return res
  }

  async function logout() {
    try {
      await logoutApi()
    } catch {
      // ignore errors
    }
    resetAuth()
  }

  async function fetchUserInfo() {
    // getUserInfoApi returns unwrapped user object (interceptor returns data.data)
    const data = await getUserInfoApi() as any
    userInfo.value = data
    roles.value = data?.roles?.map((r: any) => r.slug) || []
    saveToStorage()
  }

  function initAuth() {
    const authData = localStorage.getItem('auth')
    if (authData) {
      try {
        const parsed = JSON.parse(authData)
        token.value = parsed.token || ''
        userInfo.value = parsed.userInfo || null
        roles.value = parsed.roles || []
      } catch {
        localStorage.removeItem('auth')
      }
    }
  }

  function resetAuth() {
    token.value = ''
    userInfo.value = null
    roles.value = []
    localStorage.removeItem('auth')
  }

  function saveToStorage() {
    // Merge with existing auth data to preserve permissionList written by generateRoutes()
    let existing = {}
    try {
      const current = localStorage.getItem('auth')
      if (current) existing = JSON.parse(current)
    } catch { /* ignore */ }
    localStorage.setItem('auth', JSON.stringify({
      ...existing,
      token: token.value,
      userInfo: userInfo.value,
      roles: roles.value,
    }))
  }

  return {
    token,
    userInfo,
    roles,
    isAuthenticated,
    login,
    logout,
    fetchUserInfo,
    initAuth,
    resetAuth,
  }
})
