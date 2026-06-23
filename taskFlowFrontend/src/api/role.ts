import request from './axios'
import type { Role, PaginationResponse, PaginationParams } from '@/types/api'

export const getRoles = (params?: PaginationParams) =>
  request.get<any, PaginationResponse<Role>>('/admin/roles', { params })

export const getRole = (hashId: string) =>
  request.get<any, Role>(`/admin/roles/${hashId}`)

export const createRole = (data: any) =>
  request.post('/admin/roles', data)

export const updateRole = (hashId: string, data: any) =>
  request.put(`/admin/roles/${hashId}`, data)

export const deleteRole = (hashId: string) =>
  request.delete(`/admin/roles/${hashId}`)

export const batchUpdateRoleStatus = (data: { ids: string[]; status: number }) =>
  request.post('/admin/roles/batch-status', data)

export const assignMenusToRole = (roleHashId: string, data: { menu_ids: string[] }) =>
  request.post(`/admin/roles/assign-menus/${roleHashId}`, data)

export const assignPermissionsToRole = (roleHashId: string, data: { permission_ids: string[] }) =>
  request.post(`/admin/roles/assign-permissions/${roleHashId}`, data)

export const getPermissionsList = () =>
  request.get<any, any[]>('/admin/roles/permissions/list')

export const getMenusList = () =>
  request.get<any, any[]>('/admin/roles/menus/list')

export const getRolePermissions = (hashId: string) =>
  request.get<any, any[]>(`/admin/roles/${hashId}/permissions`)

export const getRoleMenus = (hashId: string) =>
  request.get<any, any[]>(`/admin/roles/${hashId}/menus`)
