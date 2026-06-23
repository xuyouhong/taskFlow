// ============================================================
// API Response Types — ALL IDs use hash_id: string
// ============================================================

export interface ApiResponse<T = any> {
  data: T
  message: string
  status_code: number
}

export interface PaginationResponse<T = any> {
  list: T[]
  total: number
  current_page: number
  per_page: number
  last_page: number
}

export interface PaginationParams {
  page?: number
  per_page?: number
  [key: string]: any
}

// ============================================================
// Entity Types
// ============================================================

export interface User {
  hash_id: string
  username: string
  email: string
  real_name: string
  avatar: string
  phone: string
  status: number
  last_login_at: string | null
  last_login_ip: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  roles: Role[]
  permissions?: Permission[]
}

export interface Role {
  hash_id: string
  name: string
  slug: string
  description: string
  status: number
  sort: number
  created_at: string
  updated_at: string
  permissions: Permission[]
  menus: Menu[]
}

export interface Permission {
  hash_id: string
  name: string
  slug: string
  http_method: string
  http_path: string
  description: string
  status: number
  sort: number
  created_at: string
  updated_at: string
}

export interface Menu {
  hash_id: string
  parent_id: string | null
  name: string
  icon: string
  path: string
  component: string
  sort: number
  type: number // 1: directory, 2: menu, 3: button
  status: number
  is_link: number
  keep_alive: number
  description: string
  created_at: string
  updated_at: string
  children?: Menu[]
}

export interface LoginLog {
  hash_id: string
  user_id: string
  username: string
  ip: string
  user_agent: string
  browser: string
  os: string
  device: string
  country: string
  region: string
  city: string
  login_at: string
  logout_at: string | null
  online_duration: number | null
  status: number
  message: string
  created_at: string
  updated_at: string
  user?: User
}

export interface OperationLog {
  hash_id: string
  user_id: string
  username: string
  method: string
  path: string
  params: any
  response: any
  ip: string
  user_agent: string
  status_code: number
  duration: number
  operated_at: string
  created_at: string
  updated_at: string
  user?: User
}

export interface AdminNotification {
  hash_id: string
  title: string
  content: string
  type: number // 1: notification, 2: announcement
  priority: number // 1: normal, 2: important, 3: urgent
  sender_id: string
  target_type: number // 1: all, 2: roles, 3: users
  target_values: string[] | null
  publish_time: string | null
  expire_time: string | null
  status: number // 1: draft, 2: published, 3: revoked
  created_at: string
  updated_at: string
  sender?: User
}

export interface UserNotification {
  hash_id: string
  notification_id: string
  user_id: string
  is_read: boolean
  read_at: string | null
  created_at: string
  updated_at: string
  notification?: AdminNotification
}

// ============================================================
// Dashboard Types
// ============================================================

export interface DashboardStats {
  user_stats: {
    total: number
    active: number
    today_login: number
  }
  login_stats: {
    total: number
    success: number
    failed: number
  }
  operation_stats: {
    total: number
    active_users: number
  }
  recent_logins: LoginLog[]
  system_info: {
    php_version: string
    laravel_version: string
    server_software: string
    database_connection: string
    timezone: string
    upload_max_filesize: string
    memory_limit: string
  }
}

export interface ChartDataItem {
  date: string
  total: number
  success?: number
  active_users?: number
}

export interface QuickStats {
  today: {
    logins: number
    operations: number
    new_users: number
  }
  yesterday: {
    logins: number
    operations: number
    new_users: number
  }
}

// ============================================================
// Auth Types
// ============================================================

export interface LoginParams {
  username: string
  password: string
  captcha_key: string
  captcha_code: string
}

export interface LoginResult {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

export interface CaptchaData {
  captcha_key: string
  captcha_image: string
}

// ============================================================
// Upload Types
// ============================================================

export interface UploadResult {
  file_name: string
  file_size: number
  file_type: string
  mime_type: string
  path: string
  url: string
}

// ============================================================
// Login Log Statistics
// ============================================================

export interface LoginLogStatItem {
  date: string
  total_logins: number
  success_logins: number
  failed_logins: number
}

export interface OperationLogStats {
  daily_stats: {
    date: string
    total_operations: number
    active_users: number
    avg_duration: number
  }[]
  method_stats: {
    method: string
    count: number
  }[]
}
