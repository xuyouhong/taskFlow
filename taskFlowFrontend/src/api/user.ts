import request from './axios'
import type { User, PaginationResponse, PaginationParams } from '@/types/api'

export const getUsers = (params: PaginationParams) =>
  request.get<any, PaginationResponse<User>>('/admin/users', { params })

export const getUser = (hashId: string) =>
  request.get<any, User>(`/admin/users/${hashId}`)

export const createUser = (data: any) =>
  request.post('/admin/users', data)

export const updateUser = (hashId: string, data: any) =>
  request.put(`/admin/users/${hashId}`, data)

export const deleteUser = (hashId: string) =>
  request.delete(`/admin/users/${hashId}`)

export const batchUpdateUserStatus = (data: { ids: string[]; status: number }) =>
  request.post('/admin/users/batch-status', data)

export const getUserRoles = () =>
  request.get<any, any[]>('/admin/users/roles/list')
