import dayjs from 'dayjs'
import timezone from 'dayjs/plugin/timezone'
import utc from 'dayjs/plugin/utc'

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.tz.setDefault('Asia/Shanghai')

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '-'
  return dayjs(value).tz('Asia/Shanghai').format('YYYY-MM-DD HH:mm:ss')
}

export function formatDate(value: string | null | undefined): string {
  if (!value) return '-'
  return dayjs(value).tz('Asia/Shanghai').format('YYYY-MM-DD')
}

export function formatTime(value: string | null | undefined): string {
  if (!value) return '-'
  return dayjs(value).tz('Asia/Shanghai').format('HH:mm:ss')
}

export function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

export function formatDuration(seconds: number | null): string {
  if (seconds === null || seconds === undefined) return '-'
  if (seconds < 60) return `${seconds}秒`
  if (seconds < 3600) return `${Math.floor(seconds / 60)}分${seconds % 60}秒`
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  return `${h}小时${m}分`
}

export function formatStatus(status: number): string {
  return status === 1 ? '启用' : '禁用'
}
