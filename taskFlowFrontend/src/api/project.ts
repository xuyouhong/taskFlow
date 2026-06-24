import request from './axios'
import type { PaginateResult } from '@/types/api'

export interface Project {
  hash_id: string
  name: string
  code: string
  description?: string
  owner_id: string
  status: number
  created_at: string
  updated_at: string
  owner?: any
  users?: any[]
}

export interface ProjectMember {
  hash_id: string
  project_id: string
  user_id: string
  role: 'owner' | 'member' | 'viewer'
  created_at: string
  user?: any
}

export const getProjects = (params?: any) =>
  request.get<any, PaginateResult<Project>>('/v1/projects', { params })

export const getProject = (hashId: string) =>
  request.get<any, Project>(`/v1/projects/${hashId}`)

export const createProject = (data: Partial<Project>) =>
  request.post<any, Project>('/v1/projects', data)

export const updateProject = (hashId: string, data: Partial<Project>) =>
  request.put<any, Project>(`/v1/projects/${hashId}`, data)

export const deleteProject = (hashId: string) =>
  request.delete(`/v1/projects/${hashId}`)

export const getProjectMembers = (hashId: string) =>
  request.get<any, ProjectMember[]>(`/v1/projects/${hashId}/members`)

export const addProjectMember = (hashId: string, data: { user_id: string; role: string }) =>
  request.post(`/v1/projects/${hashId}/members`, data)

export const removeProjectMember = (hashId: string, userId: string) =>
  request.delete(`/v1/projects/${hashId}/members/${userId}`)
