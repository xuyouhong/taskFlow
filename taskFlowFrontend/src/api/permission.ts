import request from './axios'
import type { Permission, PaginationResponse, PaginationParams } from '@/types/api'

export const getPermissions = (params?: PaginationParams) =>
  request.get<any, PaginationResponse<Permission>>('/admin/permissions', { params })

export const getPermission = (hashId: string) =>
  request.get<any, Permission>(`/admin/permissions/${hashId}`)

export const createPermission = (data: any) =>
  request.post('/admin/permissions', data)

export const updatePermission = (hashId: string, data: any) =>
  request.put(`/admin/permissions/${hashId}`, data)

export const deletePermission = (hashId: string) =>
  request.delete(`/admin/permissions/${hashId}`)

export const batchUpdatePermissionStatus = (data: { ids: string[]; status: number }) =>
  request.post('/admin/permissions/batch-status', data)

export const syncRoutes = () =>
  request.post('/admin/permissions/sync-routes')
