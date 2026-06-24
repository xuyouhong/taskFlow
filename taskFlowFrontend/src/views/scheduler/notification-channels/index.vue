<template>
  <div class="notification-channel-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item label="渠道类型">
          <el-select v-model="searchForm.type" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="Email" value="email" />
            <el-option label="Webhook" value="webhook" />
            <el-option label="钉钉" value="dingtalk" />
            <el-option label="企业微信" value="wecom" />
            <el-option label="飞书" value="feishu" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>搜索
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>重置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- Table Card -->
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <div class="toolbar-left">
          <el-button v-permission="['notification-channels.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>创建
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" label="渠道名称" min-width="150" show-overflow-tooltip />
        <el-table-column label="类型" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ getTypeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="config" label="配置" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">{{ JSON.stringify(row.config) }}</template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="创建时间" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.created_at) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="160" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button v-permission="['notification-channels.destroy']" type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination :pagination="pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <NotificationChannelFormDialog v-model:visible="dialogVisible" :edit-data="currentRow" @submit="loadData" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, Plus } from '@element-plus/icons-vue'
import type { NotificationChannel } from '@/api/notificationChannel'
import { getNotificationChannels, deleteNotificationChannel } from '@/api/notificationChannel'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import NotificationChannelFormDialog from './components/NotificationChannelFormDialog.vue'
import { formatDateTime } from '@/utils/format'

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
} = useTable<NotificationChannel>({
  fetchApi: getNotificationChannels,
  defaultSearch: {
    type: '' as string,
    status: '' as string | number,
  },
})

const dialogVisible = ref(false)
const currentRow = ref<NotificationChannel | null>(null)

function getTypeLabel(type: string) {
  const map: Record<string, string> = { email: 'Email', webhook: 'Webhook', dingtalk: '钉钉', wecom: '企业微信', feishu: '飞书' }
  return map[type] || type
}

function handleCreate() {
  currentRow.value = null
  dialogVisible.value = true
}

function handleEdit(row: NotificationChannel) {
  currentRow.value = row
  dialogVisible.value = true
}

async function handleDelete(row: NotificationChannel) {
  try {
    await ElMessageBox.confirm('确定要删除该通知渠道吗？', '提示', { type: 'warning' })
    await deleteNotificationChannel(row.hash_id)
    ElMessage.success('删除成功')
    loadData()
  } catch {}
}
</script>

<style scoped>
.notification-channel-container { padding: 0; }
.search-card { margin-bottom: 16px; }
.search-card :deep(.el-card__body) { padding-bottom: 0; }
.table-card :deep(.el-card__body) { padding-top: 16px; }
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
</style>
