import request from './axios'
import type { DashboardStats, ChartDataItem, QuickStats, LoginLog } from '@/types/api'

export const getDashboard = () =>
  request.get<any, DashboardStats>('/admin/dashboard')

export const getChartData = (params: { type: 'login' | 'operation'; days: number }) =>
  request.get<any, ChartDataItem[]>('/admin/dashboard/chart', { params })

export const getQuickStats = () =>
  request.get<any, QuickStats>('/admin/dashboard/quick-stats')

export const getRecentLogins = (params?: { limit?: number }) =>
  request.get<any, LoginLog[]>('/admin/dashboard/recent-logins', { params })
