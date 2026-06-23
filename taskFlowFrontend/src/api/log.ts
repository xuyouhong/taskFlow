import request from './axios'
import type { LoginLog, OperationLog, PaginationResponse, PaginationParams, LoginLogStatItem, OperationLogStats } from '@/types/api'

// Login Logs
export const getLoginLogs = (params?: PaginationParams) =>
  request.get<any, PaginationResponse<LoginLog>>('/admin/login-logs', { params })

export const getLoginLog = (hashId: string) =>
  request.get<any, LoginLog>(`/admin/login-logs/${hashId}`)

export const deleteLoginLog = (hashId: string) =>
  request.delete(`/admin/login-logs/${hashId}`)

export const batchDeleteLoginLogs = (data: { ids: string[] }) =>
  request.post('/admin/login-logs/batch-destroy', data)

export const getLoginLogStatistics = (params: { days: number }) =>
  request.get<any, LoginLogStatItem[]>('/admin/login-logs/statistics', { params })

// Operation Logs
export const getOperationLogs = (params?: PaginationParams) =>
  request.get<any, PaginationResponse<OperationLog>>('/admin/operation-logs', { params })

export const getOperationLog = (hashId: string) =>
  request.get<any, OperationLog>(`/admin/operation-logs/${hashId}`)

export const deleteOperationLog = (hashId: string) =>
  request.delete(`/admin/operation-logs/${hashId}`)

export const batchDeleteOperationLogs = (data: { ids: string[] }) =>
  request.post('/admin/operation-logs/batch-destroy', data)

export const cleanOperationLogs = (data: { days: number }) =>
  request.post('/admin/operation-logs/clean', data)

export const getOperationLogStatistics = (params: { days: number }) =>
  request.get<any, OperationLogStats>('/admin/operation-logs/statistics', { params })
