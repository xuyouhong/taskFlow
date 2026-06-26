/**
 * Shared mapping from Chinese menu names (returned by backend) to i18n keys.
 * Used by Sidebar, Breadcrumb, TagsView, and SearchPanel.
 * This is a fallback — dynamic path-based mapping is preferred for new menus.
 */
export const menuNameMap: Record<string, string> = {
  // Directory menus
  '系统管理': 'layout.systemManagement',
  '日志管理': 'layout.logManagement',
  '通知通告': 'layout.notificationsAnnouncements',
  '通知管理': 'layout.notificationManagement',
  '任务调度': 'scheduler.title',
  'Dashboard': 'layout.dashboard',
  // Page menus
  '首页': 'layout.dashboard',
  '用户管理': 'layout.userManagement',
  '用户列表': 'layout.userManagement',
  '角色管理': 'layout.roleManagement',
  '角色列表': 'layout.roleManagement',
  '权限管理': 'layout.permissionManagement',
  '权限列表': 'layout.permissionManagement',
  '菜单管理': 'layout.menuManagement',
  '菜单列表': 'layout.menuManagement',
  '登录日志': 'layout.loginLog',
  '操作日志': 'layout.operationLog',
  '系统通知': 'layout.adminNotification',
  '仪表盘': 'layout.dashboard',
  '个人信息': 'layout.profile',
  '修改密码': 'layout.password',
  // Scheduler menus
  '任务管理': 'scheduler.taskManagement',
  '任务日志': 'scheduler.taskLog',
  '执行日志': 'scheduler.executionLog',
  '节点管理': 'scheduler.nodeManagement',
  '项目管理': 'scheduler.projectManagement',
  '通知渠道': 'scheduler.notificationChannelManagement',
  // i18n key patterns from route meta.title (static routes)
  'dashboard.title': 'layout.dashboard',
  'profile.title': 'layout.profile',
  'password.title': 'layout.password',
}

/**
 * Generate i18n key from menu path for dynamic menu translation.
 * Example: '/system/users' → 'menu.system.users'
 * Example: '/scheduler/task-logs' → 'menu.scheduler.taskLogs'
 */
export function getMenuI18nKey(path: string): string {
  if (!path) return ''
  const cleanPath = path.replace(/^\/+/, '').replace(/\/+$/, '')
  if (!cleanPath) return 'menu.home'
  const segments = cleanPath.split('/')
  const camelCased = segments.map((seg, i) => {
    if (i === 0) return seg
    return seg.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())
  })
  return `menu.${camelCased.join('.')}`
}

/**
 * Translate menu name using i18n.
 * Priority: 1. static menuNameMap  2. dynamic path-based key  3. fallback to original name
 */
export function translateMenuName(name: string, path?: string, t?: (key: string) => string): string {
  if (!t) return name
  const staticKey = menuNameMap[name]
  if (staticKey) {
    const translated = t(staticKey)
    if (translated !== staticKey) return translated
  }
  if (path) {
    const pathKey = getMenuI18nKey(path)
    if (pathKey) {
      const translated = t(pathKey)
      if (translated !== pathKey) return translated
    }
  }
  return name
}

/**
 * Icon name mapping from backend icon class names to Element Plus icon component names.
 */
export const iconMap: Record<string, string> = {
  'el-icon-user': 'User',
  'el-icon-s-custom': 'UserFilled',
  'el-icon-menu': 'Menu',
  'el-icon-setting': 'Setting',
  'el-icon-document': 'Document',
  'el-icon-s-grid': 'Grid',
  'el-icon-s-order': 'List',
  'el-icon-bell': 'Bell',
  'el-icon-s-home': 'HomeFilled',
  'el-icon-s-tools': 'Tools',
  'el-icon-s-flag': 'Flag',
  'el-icon-s-data': 'DataAnalysis',
  'el-icon-s-operation': 'Operation',
  'el-icon-s-check': 'CircleCheck',
  'el-icon-s-release': 'Unlock',
  'el-icon-s-platform': 'Monitor',
  'el-icon-s-fold': 'Fold',
  'el-icon-s-unfold': 'Expand',
}
