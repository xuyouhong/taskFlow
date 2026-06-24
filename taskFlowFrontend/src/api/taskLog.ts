import request from './axios'
import type { PaginateResult } from '@/types/api'

export interface TaskLog {
  hash_id: string
  task_id: string
  trigger_id: string
  execution_id: string
  trigger_type: 'schedule' | 'manual' | 'retry'
  status: 'pending' | 'running' | 'success' | 'failed' | 'timeout' | 'cancelled'
  node_id?: string
  start_time?: string
  end_time?: string
  duration_ms?: number
  request_snapshot?: any
  response_summary?: string
  error_message?: string
  retry_count: number
  created_at: string
  task?: any
  node?: any
  detail?: TaskLogDetail
}

export interface TaskLogDetail {
  hash_id: string
  task_log_id: string
  stdout_content?: string
  stderr_content?: string
  created_at: string
}

export const getTaskLogs = (params?: any) =>
  request.get<any, PaginateResult<TaskLog>>('/v1/logs', { params })

export const getTaskLog = (hashId: string) =>
  request.get<any, TaskLog>(`/v1/logs/${hashId}`)

export const getArchiveLogs = (params?: any) =>
  request.get<any, PaginateResult<TaskLog>>('/v1/logs/archive', { params })
