import axios from 'axios'
import type { AxiosInstance, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'

const request: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 15000,
  headers: { 'Content-Type': 'application/json;charset=UTF-8' },
})

let isShowingReLoginModal = false

// Request interceptor: attach Bearer token from localStorage
request.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const authData = localStorage.getItem('auth')
    if (authData) {
      try {
        const { token } = JSON.parse(authData)
        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }
      } catch {
        // ignore parse errors
      }
    }
    return config
  },
  (error) => Promise.reject(error),
)

// Filter memory addresses from response messages
const filterMemoryAddresses = (obj: any): any => {
  if (obj === null || obj === undefined) return obj
  if (Array.isArray(obj)) return obj.map((item) => filterMemoryAddresses(item))
  if (typeof obj === 'object') {
    const filtered = { ...obj }
    for (const key in filtered) {
      if (Object.prototype.hasOwnProperty.call(filtered, key)) {
        filtered[key] = filterMemoryAddresses(filtered[key])
        if (key === 'message' && typeof filtered[key] === 'string') {
          filtered[key] = filtered[key].replace(/\[(0x[0-9a-fA-F]+\s*)+\]/g, '').trim()
        }
      }
    }
    return filtered
  }
  return obj
}

// Response interceptor: unwrap { data, message, status_code } envelope
request.interceptors.response.use(
  (response: AxiosResponse) => {
    const { data } = response
    const url = response.config.url || ''

    // Login endpoint: return raw envelope (auth store handles res.data.access_token)
    if (url.includes('/login') && response.config.method === 'post') {
      return data
    }

    // Upload endpoint: return data from message field or envelope
    if (url.includes('/upload')) {
      return filterMemoryAddresses(data.data || data.message || data)
    }

    // Normal endpoints: unwrap data.data
    if (data.status_code === 200 || data.code === 200) {
      return filterMemoryAddresses(data.data !== undefined ? data.data : data)
    }

    // API returned error in body
    ElMessage.error(data.message || '请求失败')
    return Promise.reject(new Error(data.message || 'Error'))
  },
  (error) => {
    if (!error.response) {
      ElMessage.error('网络连接失败，请检查网络')
      return Promise.reject(error)
    }
    const { status, data } = error.response
    const config = error.config || {}

    // Login 400: let caller handle it
    if (config.url?.includes('/login') && status === 400) {
      return Promise.reject(error)
    }

    switch (status) {
      case 401:
        if (config.url?.includes('/logout')) {
          localStorage.removeItem('auth')
          window.location.href = '/login'
          return Promise.reject(error)
        }
        if (!isShowingReLoginModal) {
          isShowingReLoginModal = true
          ElMessageBox.confirm('登录已过期，请重新登录', '提示', {
            confirmButtonText: '重新登录',
            cancelButtonText: '取消',
            type: 'warning',
            closeOnClickModal: false,
            closeOnPressEscape: false,
            beforeClose: (_action, _instance, done) => {
              isShowingReLoginModal = false
              done()
            },
          })
            .then(() => {
              localStorage.removeItem('auth')
              window.location.href = '/login'
            })
            .catch(() => {})
        }
        break
      case 403:
        ElMessage.error('没有权限访问')
        break
      case 404:
        ElMessage.error('请求的资源不存在')
        break
      case 429:
        ElMessage.error(data?.message || '请求过于频繁，请稍后再试')
        break
      case 500:
        ElMessage.error('服务器内部错误')
        break
      default:
        ElMessage.error(data?.message || `请求失败 (${status})`)
    }
    return Promise.reject(error)
  },
)

export default request
