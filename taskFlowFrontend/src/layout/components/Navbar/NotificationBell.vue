<template>
  <el-dropdown ref="dropdownRef" trigger="click" @visible-change="handleVisibleChange">
    <div class="navbar-action">
      <el-badge :value="unreadCount" :hidden="unreadCount === 0" :max="99">
        <el-icon :size="18"><Bell /></el-icon>
      </el-badge>
    </div>
    <template #dropdown>
      <el-dropdown-menu class="notification-dropdown">
        <div class="notification-header">
          <span>{{ t('layout.notification') }}</span>
          <el-button link type="primary" @click="handleMarkAllRead" :disabled="unreadCount === 0">
            {{ t('layout.markAllRead') }}
          </el-button>
        </div>
        <el-scrollbar :height="scrollbarHeight">
          <div v-if="notifications.length === 0" class="notification-empty">
            {{ t('layout.noNotification') }}
          </div>
          <div
            v-for="item in notifications"
            :key="item.hash_id"
            class="notification-item"
            :class="{ unread: !item.is_read }"
            @click="handleClick(item)"
          >
            <div class="notification-row">
              <span class="unread-dot" :class="{ visible: !item.is_read }"></span>
              <span class="notification-title">
                {{ item.notification?.title }}
              </span>
              <el-tag
                size="small"
                :type="item.notification?.type === 2 ? 'warning' : 'primary'"
                class="notification-type"
              >
                {{ item.notification?.type === 2 ? t('notification.typeAnnouncement') : t('notification.typeNotification') }}
              </el-tag>
            </div>
            <div class="notification-time">{{ formatDateTime(item.created_at) }}</div>
          </div>
        </el-scrollbar>
        <div v-if="totalPages > 1" class="notification-pagination">
          <el-pagination
            v-model:current-page="currentPage"
            :page-size="pageSize"
            :total="total"
            layout="prev, pager, next"
            small
            @current-change="handlePageChange"
          />
        </div>
        <div v-if="hasNotificationPermission" class="notification-footer">
          <router-link to="/notifications/index" @click="handleViewAll">
            {{ t('layout.viewAll') }}
          </router-link>
        </div>
      </el-dropdown-menu>
    </template>
  </el-dropdown>

  <!-- Notification Detail Dialog -->
  <el-dialog
    v-model="detailVisible"
    :title="t('layout.notificationDetail')"
    width="560px"
    draggable
    :close-on-click-modal="false"
    append-to-body
  >
    <div v-if="currentDetail" class="notification-detail">
      <div class="detail-header">
        <h3>{{ currentDetail.notification?.title }}</h3>
        <div class="detail-meta">
          <el-tag
            size="small"
            :type="currentDetail.notification?.type === 2 ? 'warning' : 'primary'"
          >
            {{ currentDetail.notification?.type === 2 ? t('notification.typeAnnouncement') : t('notification.typeNotification') }}
          </el-tag>
          <el-tag
            size="small"
            :type="priorityTagType(currentDetail.notification?.priority)"
          >
            {{ priorityLabel(currentDetail.notification?.priority) }}
          </el-tag>
          <span class="detail-time" v-if="currentDetail.notification?.publish_time">
            {{ formatDateTime(currentDetail.notification.publish_time) }}
          </span>
        </div>
      </div>
      <el-divider />
      <div class="detail-content" v-html="currentDetail.notification?.content"></div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bell } from '@element-plus/icons-vue'
import type { DropdownInstance } from 'element-plus'
import { getUserNotifications, getUnreadCount, getUserNotification, markAllAsRead } from '@/api/notification'
import { formatDateTime } from '@/utils/format'
import { usePermissionStore } from '@/stores/permission'
import type { UserNotification } from '@/types/api'

const { t } = useI18n()
const permissionStore = usePermissionStore()

const dropdownRef = ref<DropdownInstance>()
const unreadCount = ref(0)
const notifications = ref<UserNotification[]>([])
const currentPage = ref(1)
const pageSize = 5
const total = ref(0)
let pollTimer: ReturnType<typeof setInterval> | null = null

// Detail dialog
const detailVisible = ref(false)
const currentDetail = ref<UserNotification | null>(null)

// Scrollbar height: fixed for consistent layout
const scrollbarHeight = '280px'

const totalPages = computed(() => Math.ceil(total.value / pageSize))

const hasNotificationPermission = computed(() => {
  return permissionStore.hasPermission('notifications.index')
})

function priorityLabel(priority?: number): string {
  switch (priority) {
    case 3: return t('notification.priorityUrgent')
    case 2: return t('notification.priorityImportant')
    default: return t('notification.priorityNormal')
  }
}

function priorityTagType(priority?: number): string {
  switch (priority) {
    case 3: return 'danger'
    case 2: return 'warning'
    default: return 'info'
  }
}

async function fetchUnreadCount() {
  try {
    const res = await getUnreadCount() as any
    unreadCount.value = res?.count || 0
  } catch {
    // ignore
  }
}

async function fetchNotifications(page = 1) {
  try {
    const res = await getUserNotifications({ per_page: pageSize, page } as any) as any
    notifications.value = res?.list || (Array.isArray(res) ? res : [])
    total.value = res?.total || 0
  } catch {
    // ignore
  }
}

function handlePageChange(page: number) {
  currentPage.value = page
  fetchNotifications(page)
}

async function handleClick(item: UserNotification) {
  try {
    const detail = await getUserNotification(item.hash_id) as any
    currentDetail.value = detail || item
    detailVisible.value = true

    // Update local read state
    if (!item.is_read) {
      item.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch {
    // Fallback: show what we already have
    currentDetail.value = item
    detailVisible.value = true
  }
}

async function handleMarkAllRead() {
  try {
    await markAllAsRead()
    unreadCount.value = 0
    notifications.value.forEach((n) => (n.is_read = true))
  } catch {
    // ignore
  }
}

function handleVisibleChange(visible: boolean) {
  if (visible) {
    currentPage.value = 1
    fetchNotifications(1)
  } else if (detailVisible.value) {
    // Prevent dropdown from closing while detail dialog is open
    nextTick(() => {
      dropdownRef.value?.handleOpen?.()
    })
  }
}

function handleViewAll() {
  // close dropdown handled by el-dropdown
}

onMounted(() => {
  fetchUnreadCount()
  pollTimer = setInterval(fetchUnreadCount, 60000)
})

onBeforeUnmount(() => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
})
</script>

<style scoped>
.notification-dropdown {
  width: 400px;
  padding: 0;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-light);
  font-weight: 600;
}

.notification-item {
  padding: 10px 16px;
  cursor: pointer;
  border-bottom: 1px solid var(--border-lighter);
  transition: background 0.2s;
}

.notification-item:hover {
  background: var(--fill-color);
}

.notification-item.unread {
  background: var(--fill-color-light);
}

.notification-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.unread-dot {
  flex-shrink: 0;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: transparent;
}

.unread-dot.visible {
  background: var(--el-color-primary);
}

.notification-title {
  flex: 1;
  min-width: 0;
  font-size: 14px;
  color: var(--text-primary-color);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.notification-type {
  flex-shrink: 0;
}

.notification-time {
  font-size: 12px;
  color: var(--text-secondary-color);
  margin-top: 4px;
  margin-left: 11px;
}

.notification-empty {
  text-align: center;
  padding: 40px 16px;
  color: var(--text-secondary-color);
}

.notification-pagination {
  display: flex;
  justify-content: center;
  padding: 8px 0;
  border-top: 1px solid var(--border-lighter);
}

.notification-footer {
  text-align: center;
  padding: 10px;
  border-top: 1px solid var(--border-light);
}

.notification-footer a {
  color: var(--primary-color);
  text-decoration: none;
}

/* Detail dialog styles */
.notification-detail {
  padding: 0 4px;
}

.detail-header h3 {
  margin: 0 0 12px 0;
  font-size: 18px;
  color: var(--el-text-color-primary);
  line-height: 1.4;
}

.detail-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.detail-time {
  font-size: 13px;
  color: var(--el-text-color-secondary);
}

.detail-content {
  font-size: 14px;
  line-height: 1.8;
  color: var(--el-text-color-primary);
  word-break: break-word;
}

.detail-content :deep(img) {
  max-width: 100%;
  height: auto;
}
</style>
