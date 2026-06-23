import request from './axios'
import type { AdminNotification, UserNotification, PaginationResponse, PaginationParams } from '@/types/api'

// Admin Notifications (management)
export const getAdminNotifications = (params?: PaginationParams) =>
  request.get<any, PaginationResponse<AdminNotification>>('/admin/notifications', { params })

export const getAdminNotification = (hashId: string) =>
  request.get<any, AdminNotification>(`/admin/notifications/${hashId}`)

export const createAdminNotification = (data: any) =>
  request.post('/admin/notifications', data)

export const updateAdminNotification = (hashId: string, data: any) =>
  request.put(`/admin/notifications/${hashId}`, data)

export const deleteAdminNotification = (hashId: string) =>
  request.delete(`/admin/notifications/${hashId}`)

export const publishNotification = (hashId: string) =>
  request.post(`/admin/notifications/${hashId}/publish`)

export const revokeNotification = (hashId: string) =>
  request.post(`/admin/notifications/${hashId}/revoke`)

// User Notifications (personal inbox)
export const getUserNotifications = (params?: PaginationParams) =>
  request.get<any, PaginationResponse<UserNotification>>('/admin/user-notifications', { params })

export const getUnreadCount = () =>
  request.get<any, { count: number }>('/admin/user-notifications/unread-count')

export const markAsRead = (hashId: string) =>
  request.post(`/admin/user-notifications/${hashId}/read`)

export const markAllAsRead = () =>
  request.post('/admin/user-notifications/read-all')

export const getUserNotification = (hashId: string) =>
  request.get<any, UserNotification>(`/admin/user-notifications/${hashId}`)

export const deleteUserNotification = (hashId: string) =>
  request.delete(`/admin/user-notifications/${hashId}`)

export const batchDeleteUserNotifications = (data: { ids: string[] }) =>
  request.post('/admin/user-notifications/batch-destroy', data)
