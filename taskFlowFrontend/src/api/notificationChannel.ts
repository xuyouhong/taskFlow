import request from './axios'
import type { PaginateResult } from '@/types/api'

export interface NotificationChannel {
  hash_id: string
  name: string
  type: 'email' | 'webhook' | 'dingtalk' | 'wecom' | 'feishu'
  config: any
  status: number
  created_at: string
  updated_at: string
}

export const getNotificationChannels = (params?: any) =>
  request.get<any, PaginateResult<NotificationChannel>>('/v1/notification-channels', { params })

export const getNotificationChannel = (hashId: string) =>
  request.get<any, NotificationChannel>(`/v1/notification-channels/${hashId}`)

export const createNotificationChannel = (data: Partial<NotificationChannel>) =>
  request.post<any, NotificationChannel>('/v1/notification-channels', data)

export const updateNotificationChannel = (hashId: string, data: Partial<NotificationChannel>) =>
  request.put<any, NotificationChannel>(`/v1/notification-channels/${hashId}`, data)

export const deleteNotificationChannel = (hashId: string) =>
  request.delete(`/v1/notification-channels/${hashId}`)
