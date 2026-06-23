import request from './axios'
import type { Menu } from '@/types/api'

export const getMenus = () =>
  request.get<any, Menu[]>('/admin/menus')

export const getMenusAll = () =>
  request.get<any, Menu[]>('/admin/menus/all')

export const getMenusTree = () =>
  request.get<any, Menu[]>('/admin/menus-tree')

export const getUserMenus = () =>
  request.get<any, Menu[]>('/admin/user-menus')

export const getMenu = (hashId: string) =>
  request.get<any, Menu>(`/admin/menus/${hashId}`)

export const createMenu = (data: any) =>
  request.post('/admin/menus', data)

export const updateMenu = (hashId: string, data: any) =>
  request.put(`/admin/menus/${hashId}`, data)

export const deleteMenu = (hashId: string) =>
  request.delete(`/admin/menus/${hashId}`)

export const updateMenuSort = (data: { menus: { id: string; sort: number }[] }) =>
  request.put('/admin/menus/sort', data)

export const batchUpdateMenuStatus = (data: { ids: string[]; status: number }) =>
  request.post('/admin/menus/batch-status', data)
