/**
 * Shared mapping from Chinese menu names (returned by backend) to i18n keys.
 * Used by Sidebar, Breadcrumb, TagsView, and SearchPanel.
 */
export const menuNameMap: Record<string, string> = {
  // Directory menus
  '系统管理': 'layout.systemManagement',
  '日志管理': 'layout.logManagement',
  '通知通告': 'layout.notificationsAnnouncements',
  '通知管理': 'layout.notificationManagement',
  // Page menus
  '首页': 'layout.dashboard',
  '用户管理': 'layout.userManagement',
  '角色管理': 'layout.roleManagement',
  '权限管理': 'layout.permissionManagement',
  '菜单管理': 'layout.menuManagement',
  '登录日志': 'layout.loginLog',
  '操作日志': 'layout.operationLog',
  '系统通知': 'layout.adminNotification',
  '仪表盘': 'layout.dashboard',
  '个人信息': 'layout.profile',
  '修改密码': 'layout.password',
  // i18n key patterns from route meta.title (static routes)
  'dashboard.title': 'layout.dashboard',
  'profile.title': 'layout.profile',
  'password.title': 'layout.password',
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
