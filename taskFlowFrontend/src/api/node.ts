import request from './axios'
import type { PaginateResult } from '@/types/api'

export interface Node {
  hash_id: string
  name: string
  ip: string
  agent_port: number
  hostname?: string
  agent_token?: string
  allowed_command_prefix?: string
  status: 'online' | 'offline'
  last_heartbeat_at?: string
  cpu_cores?: number
  memory_total_mb?: number
  agent_version?: string
  created_at: string
  updated_at: string
}

export const getNodes = (params?: any) =>
  request.get<any, PaginateResult<Node>>('/v1/nodes', { params })

export const getNode = (hashId: string) =>
  request.get<any, Node>(`/v1/nodes/${hashId}`)

export const createNode = (data: Partial<Node>) =>
  request.post<any, Node>('/v1/nodes', data)

export const updateNode = (hashId: string, data: Partial<Node>) =>
  request.put<any, Node>(`/v1/nodes/${hashId}`, data)

export const deleteNode = (hashId: string) =>
  request.delete(`/v1/nodes/${hashId}`)
