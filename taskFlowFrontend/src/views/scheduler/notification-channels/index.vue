<template>
  <div class="notification-channel-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('scheduler.channelType')">
          <el-select v-model="searchForm.type" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.typeEmail')" value="email" />
            <el-option :label="t('scheduler.typeWebhook')" value="webhook" />
            <el-option :label="t('scheduler.typeDingtalk')" value="dingtalk" />
            <el-option :label="t('scheduler.typeWecom')" value="wecom" />
            <el-option :label="t('scheduler.typeFeishu')" value="feishu" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('scheduler.channelStatus')">
          <el-select v-model="searchForm.status" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.channelEnabled')" :value="1" />
            <el-option :label="t('scheduler.channelDisabled')" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>{{ t('common.search') }}
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>{{ t('common.reset') }}
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- Table Card -->
    <el-card shadow="never" class="table-card">
      <div class="table-toolbar">
        <div class="toolbar-left">
          <el-button v-permission="['notification-channels.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>{{ t('scheduler.createChannel') }}
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" :label="t('scheduler.channelName')" min-width="150" show-overflow-tooltip />
        <el-table-column :label="t('scheduler.channelType')" width="120" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ getTypeLabel(row.type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="config" :label="t('scheduler.channelConfig')" min-width="200" show-overflow-tooltip>
          <template #default="{ row }">{{ JSON.stringify(row.config) }}</template>
        </el-table-column>
        <el-table-column :label="t('scheduler.channelStatus')" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? t('scheduler.channelEnabled') : t('scheduler.channelDisabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.channelCreatedAt')" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.created_at) }}</template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="160" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">{{ t('common.edit') }}</el-button>
            <el-button v-permission="['notification-channels.destroy']" type="danger" link size="small" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
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
  const map: Record<string, string> = {
    email: t('scheduler.typeEmail'),
    webhook: t('scheduler.typeWebhook'),
    dingtalk: t('scheduler.typeDingtalk'),
    wecom: t('scheduler.typeWecom'),
    feishu: t('scheduler.typeFeishu'),
  }
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
    await ElMessageBox.confirm(t('scheduler.channelDeleteConfirm'), t('common.tip'), { type: 'warning' })
    await deleteNotificationChannel(row.hash_id)
    ElMessage.success(t('scheduler.deleteSuccess'))
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
