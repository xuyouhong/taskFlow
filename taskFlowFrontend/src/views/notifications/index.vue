<template>
  <div class="notifications-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('notification.notificationTitle')">
          <el-input
            v-model="searchForm.title"
            :placeholder="t('notification.titlePlaceholder')"
            clearable
            style="width: 180px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('notification.type')">
          <el-select v-model="searchForm.type" clearable style="width: 130px">
            <el-option :label="t('common.all')" value="" />
            <el-option :label="t('notification.typeNotification')" :value="1" />
            <el-option :label="t('notification.typeAnnouncement')" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('notification.status')">
          <el-select v-model="searchForm.status" clearable style="width: 130px">
            <el-option :label="t('common.all')" value="" />
            <el-option :label="t('notification.statusDraft')" :value="1" />
            <el-option :label="t('notification.statusPublished')" :value="2" />
            <el-option :label="t('notification.statusRevoked')" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('notification.priority')">
          <el-select v-model="searchForm.priority" clearable style="width: 130px">
            <el-option :label="t('common.all')" value="" />
            <el-option :label="t('notification.priorityNormal')" :value="1" />
            <el-option :label="t('notification.priorityImportant')" :value="2" />
            <el-option :label="t('notification.priorityUrgent')" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="handleSearch">
            {{ t('common.search') }}
          </el-button>
          <el-button :icon="Refresh" @click="handleReset">
            {{ t('common.reset') }}
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- Toolbar + Table -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="toolbar">
          <div class="toolbar-left">
            <el-button
              v-permission="['notifications.store']"
              type="primary"
              :icon="Plus"
              @click="handleCreate"
            >
              {{ t('common.create') }}
            </el-button>
          </div>
          <div class="toolbar-right">
            <el-button :icon="Refresh" @click="loadData">
              {{ t('common.refresh') }}
            </el-button>
          </div>
        </div>
      </template>

      <el-table v-loading="loading" :data="tableData" border stripe>
        <el-table-column prop="title" :label="t('notification.notificationTitle')" min-width="200" show-overflow-tooltip />
        <el-table-column prop="type" :label="t('notification.type')" width="100" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.type === 1" size="small">{{ t('notification.typeNotification') }}</el-tag>
            <el-tag v-else type="warning" size="small">{{ t('notification.typeAnnouncement') }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="priority" :label="t('notification.priority')" width="100" align="center">
          <template #default="{ row }">
            <el-tag
              :type="priorityTagType(row.priority)"
              size="small"
              effect="dark"
            >
              {{ priorityLabel(row.priority) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="status" :label="t('notification.status')" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="statusTagType(row.status)" size="small">
              {{ statusLabel(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('notification.sender')" width="120">
          <template #default="{ row }">
            {{ row.sender?.real_name || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="target_type" :label="t('notification.targetType')" width="110" align="center">
          <template #default="{ row }">
            {{ targetTypeLabel(row.target_type) }}
          </template>
        </el-table-column>
        <el-table-column prop="publish_time" :label="t('notification.publishTime')" width="170">
          <template #default="{ row }">
            {{ formatDateTime(row.publish_time) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="240" align="center" fixed="right">
          <template #default="{ row }">
            <el-button
              v-permission="['notifications.update']"
              type="primary"
              link
              size="small"
              @click="handleEdit(row)"
            >
              {{ t('common.edit') }}
            </el-button>
            <el-button
              v-if="row.status !== 2"
              v-permission="['notifications.publish']"
              type="success"
              link
              size="small"
              @click="handlePublish(row)"
            >
              {{ t('common.publish') }}
            </el-button>
            <el-button
              v-if="row.status === 2"
              v-permission="['notifications.revoke']"
              type="warning"
              link
              size="small"
              @click="handleRevoke(row)"
            >
              {{ t('common.revoke') }}
            </el-button>
            <el-button
              v-permission="['notifications.destroy']"
              type="danger"
              link
              size="small"
              @click="handleDelete(row)"
            >
              {{ t('common.delete') }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination
        :pagination="pagination"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </el-card>

    <!-- Create / Edit Dialog -->
    <NotificationFormDialog
      v-model:visible="formDialogVisible"
      :edit-data="editingRow"
      @submit="handleFormSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Plus } from '@element-plus/icons-vue'
import { useTable } from '@/composables/useTable'
import {
  getAdminNotifications,
  deleteAdminNotification,
  publishNotification,
  revokeNotification,
} from '@/api/notification'
import { formatDateTime } from '@/utils/format'
import Pagination from '@/components/Pagination/index.vue'
import NotificationFormDialog from './components/NotificationFormDialog.vue'
import type { AdminNotification } from '@/types/api'

const { t } = useI18n()

const {
  tableData,
  loading,
  pagination,
  searchForm,
  loadData,
  handleSearch,
  handleReset,
  handleSizeChange,
  handleCurrentChange,
} = useTable<AdminNotification>({
  fetchApi: getAdminNotifications,
  defaultSearch: {
    title: '',
    type: '',
    status: '',
    priority: '',
  },
})

// ---------- Tag helpers ----------
function priorityTagType(priority: number): 'info' | 'warning' | 'danger' {
  const map: Record<number, 'info' | 'warning' | 'danger'> = { 1: 'info', 2: 'warning', 3: 'danger' }
  return map[priority] || 'info'
}

function priorityLabel(priority: number): string {
  const map: Record<number, string> = {
    1: t('notification.priorityNormal'),
    2: t('notification.priorityImportant'),
    3: t('notification.priorityUrgent'),
  }
  return map[priority] || '-'
}

function statusTagType(status: number): 'info' | 'success' | 'warning' {
  const map: Record<number, 'info' | 'success' | 'warning'> = { 1: 'info', 2: 'success', 3: 'warning' }
  return map[status] || 'info'
}

function statusLabel(status: number): string {
  const map: Record<number, string> = {
    1: t('notification.statusDraft'),
    2: t('notification.statusPublished'),
    3: t('notification.statusRevoked'),
  }
  return map[status] || '-'
}

function targetTypeLabel(targetType: number): string {
  const map: Record<number, string> = {
    1: t('notification.targetAll'),
    2: t('notification.targetRoles'),
    3: t('notification.targetUsers'),
  }
  return map[targetType] || '-'
}

// ---------- Create / Edit ----------
const formDialogVisible = ref(false)
const editingRow = ref<AdminNotification | null>(null)

function handleCreate() {
  editingRow.value = null
  formDialogVisible.value = true
}

function handleEdit(row: AdminNotification) {
  editingRow.value = row
  formDialogVisible.value = true
}

function handleFormSubmit() {
  formDialogVisible.value = false
  loadData()
}

// ---------- Publish ----------
async function handlePublish(row: AdminNotification) {
  try {
    await ElMessageBox.confirm(t('notification.publishConfirm'), t('common.tip'), { type: 'warning' })
    await publishNotification(row.hash_id)
    ElMessage.success(t('notification.publishSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

// ---------- Revoke ----------
async function handleRevoke(row: AdminNotification) {
  try {
    await ElMessageBox.confirm(t('notification.revokeConfirm'), t('common.tip'), { type: 'warning' })
    await revokeNotification(row.hash_id)
    ElMessage.success(t('notification.revokeSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

// ---------- Delete ----------
async function handleDelete(row: AdminNotification) {
  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), { type: 'warning' })
    await deleteAdminNotification(row.hash_id)
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}
</script>

<style scoped>
.notifications-container {
  padding: 0;
}
.search-card {
  margin-bottom: 16px;
}
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.toolbar-left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
