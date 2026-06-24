import request from './axios'
import type { PaginateResult } from '@/types/api'

export interface Task {
  hash_id: string
  project_id: string
  name: string
  description?: string
  cron_expression: string
  timezone: string
  executor_type: 'http' | 'shell' | 'job' | 'mq'
  executor_config: any
  retry_times: number
  retry_interval: number
  timeout: number
  concurrency_strategy: 'allow' | 'forbid' | 'replace'
  misfire_strategy: 'skip' | 'fire_once' | 'fire_all'
  priority: number
  status: 'enabled' | 'disabled' | 'paused'
  last_run_at?: string
  next_run_at?: string
  last_run_status?: 'success' | 'failed' | 'timeout' | 'running'
  created_by: string
  created_at: string
  updated_at: string
  project?: any
  creator?: any
  notifications?: any[]
}

export const getTasks = (params?: any) =>
  request.get<any, PaginateResult<Task>>('/v1/tasks', { params })

export const getTask = (hashId: string) =>
  request.get<any, Task>(`/v1/tasks/${hashId}`)

export const createTask = (data: Partial<Task>) =>
  request.post<any, Task>('/v1/tasks', data)

export const updateTask = (hashId: string, data: Partial<Task>) =>
  request.put<any, Task>(`/v1/tasks/${hashId}`, data)

export const deleteTask = (hashId: string) =>
  request.delete(`/v1/tasks/${hashId}`)

export const triggerTask = (hashId: string) =>
  request.post<any, { task_id: string }>(`/v1/tasks/${hashId}/trigger`)

export const pauseTask = (hashId: string) =>
  request.post<any, Task>(`/v1/tasks/${hashId}/pause`)

export const resumeTask = (hashId: string) =>
  request.post<any, Task>(`/v1/tasks/${hashId}/resume`)

export const getTaskLogs = (hashId: string, params?: any) =>
  request.get<any, PaginateResult<any>>(`/v1/tasks/${hashId}/logs`, { params })
