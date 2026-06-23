import request from './axios'
import type { UploadResult } from '@/types/api'

export const uploadFile = (file: File) => {
  const formData = new FormData()
  formData.append('file', file)
  return request.post<any, UploadResult>('/admin/upload', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export const batchUploadFiles = (files: File[]) => {
  const formData = new FormData()
  files.forEach((file) => formData.append('files[]', file))
  return request.post<any, UploadResult[]>('/admin/upload/batch', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}
