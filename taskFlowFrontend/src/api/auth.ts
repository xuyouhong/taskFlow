import request from './axios'
import type { CaptchaData, LoginParams, LoginResult, User } from '@/types/api'

export const getCaptcha = () => request.get<any, { data: CaptchaData }>('/admin/captcha')

export const login = (data: LoginParams) => request.post<any, { data: LoginResult }>('/admin/login', data)

export const logout = () => request.post('/admin/logout')

export const refreshToken = () => request.post('/admin/refresh')

export const getUserInfo = () => request.get<any, User>('/admin/user')

export const getUserMenus = () => request.get<any, any[]>('/admin/user-menus')

export const updateProfile = (data: Partial<User>) => request.put('/admin/profile', data)

export const changePassword = (data: { old_password: string; new_password: string; new_password_confirmation: string }) =>
  request.put('/admin/password', data)
