<template>
  <div class="task-log-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('scheduler.task')">
          <el-select v-model="searchForm.task_id" :placeholder="t('scheduler.taskPlaceholder')" clearable filterable style="width: 200px" @clear="handleSearch">
            <el-option v-for="t in tasks" :key="t.hash_id" :label="t.name" :value="t.hash_id" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('scheduler.status')">
          <el-select v-model="searchForm.status" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.statusPending')" value="pending" />
            <el-option :label="t('scheduler.statusRunning')" value="running" />
            <el-option :label="t('scheduler.statusSuccess')" value="success" />
            <el-option :label="t('scheduler.statusFailed')" value="failed" />
            <el-option :label="t('scheduler.statusTimeout')" value="timeout" />
            <el-option :label="t('scheduler.statusCancelled')" value="cancelled" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('scheduler.triggerType')">
          <el-select v-model="searchForm.trigger_type" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.triggerTypeSchedule')" value="schedule" />
            <el-option :label="t('scheduler.triggerTypeManual')" value="manual" />
            <el-option :label="t('scheduler.triggerTypeRetry')" value="retry" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('scheduler.dateRange')">
          <el-date-picker v-model="dateRange" type="daterange" :range-separator="t('scheduler.to')" :start-placeholder="t('scheduler.startDate')" :end-placeholder="t('scheduler.endDate')" value-format="YYYY-MM-DD" @change="handleDateChange" />
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
          <el-button type="info" @click="handleViewArchive" :disabled="!isArchiveQuery">{{ t('scheduler.viewArchive') }}</el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="task.name" :label="t('scheduler.taskName')" min-width="150" show-overflow-tooltip />
        <el-table-column :label="t('scheduler.triggerType')" width="100" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ getTriggerTypeLabel(row.trigger_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.status')" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.executionNode')" width="120">
          <template #default="{ row }">{{ row.node?.name || '-' }}</template>
        </el-table-column>
        <el-table-column :label="t('scheduler.startTime')" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.start_time) }}</template>
        </el-table-column>
        <el-table-column :label="t('scheduler.endTime')" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.end_time) }}</template>
        </el-table-column>
        <el-table-column :label="t('scheduler.duration')" width="100" align="center">
          <template #default="{ row }">{{ row.duration_ms ? row.duration_ms + 'ms' : '-' }}</template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="100" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleViewDetail(row)">{{ t('common.detail') }}</el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination :pagination="pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <!-- Detail Dialog -->
    <el-dialog v-model="detailVisible" :title="t('scheduler.executionDetail')" width="900px" draggable destroy-on-close>
      <template v-if="currentLog">
        <el-descriptions :column="2" border size="default">
          <el-descriptions-item :label="t('scheduler.taskName')">{{ currentLog.task?.name }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.triggerId')">{{ currentLog.trigger_id || '-' }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.executionId')">{{ currentLog.execution_id || '-' }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.triggerType')">
            <el-tag size="small">{{ getTriggerTypeLabel(currentLog.trigger_type) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.status')">
            <el-tag :type="getStatusType(currentLog.status)" size="small">{{ getStatusLabel(currentLog.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.executionNode')">{{ currentLog.node?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.startTime')">{{ formatDateTime(currentLog.start_time) }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.endTime')">{{ formatDateTime(currentLog.end_time) }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.duration')">{{ currentLog.duration_ms ? currentLog.duration_ms + ' ms' : '-' }}</el-descriptions-item>
          <el-descriptions-item :label="t('scheduler.retryCount')">{{ currentLog.retry_count ?? 0 }}</el-descriptions-item>
        </el-descriptions>

        <!-- 错误信息 -->
        <div v-if="currentLog.error_message" class="json-section">
          <div class="json-section-title">
            <el-icon><Warning /></el-icon>
            {{ t('scheduler.errorMessage') }}
          </div>
          <el-input
            type="textarea"
            :model-value="currentLog.error_message"
            :rows="4"
            readonly
            class="json-viewer error-viewer"
          />
        </div>

        <!-- 响应摘要 -->
        <div class="json-section">
          <div class="json-section-title">{{ t('scheduler.responseSummary') }}</div>
          <el-input
            type="textarea"
            :model-value="formatJson(currentLog.response_summary)"
            :rows="6"
            readonly
            class="json-viewer"
          />
        </div>

        <!-- 执行输出 -->
        <div v-if="currentLog?.detail" class="json-section">
          <div class="json-section-title">{{ t('scheduler.executionOutput') }}</div>
          <el-tabs>
            <el-tab-pane :label="t('scheduler.stdout')">
              <el-input
                type="textarea"
                :model-value="formatJson(currentLog.detail.stdout_content)"
                :rows="8"
                readonly
                class="json-viewer"
              />
            </el-tab-pane>
            <el-tab-pane :label="t('scheduler.stderr')">
              <el-input
                type="textarea"
                :model-value="getStderrDisplay()"
                :rows="8"
                readonly
                class="json-viewer"
                :class="{ 'error-output': currentLog.detail.stderr_content }"
              />
            </el-tab-pane>
            <el-tab-pane :label="t('scheduler.requestSnapshot')">
              <el-input
                type="textarea"
                :model-value="formatJson(currentLog.request_snapshot)"
                :rows="8"
                readonly
                class="json-viewer"
              />
            </el-tab-pane>
          </el-tabs>
        </div>
      </template>
      <template #footer>
        <el-button @click="detailVisible = false">{{ t('common.close') }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { Refresh, Warning } from '@element-plus/icons-vue'
import type { TaskLog } from '@/api/taskLog'
import { getTaskLogs, getTaskLog } from '@/api/taskLog'
import { getTasks } from '@/api/task'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import { formatDateTime } from '@/utils/format'

const { t } = useI18n()
const tasks = ref<any[]>([])
const dateRange = ref<[string, string] | null>(null)
const detailVisible = ref(false)
const currentLog = ref<TaskLog | null>(null)

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
} = useTable<TaskLog>({
  fetchApi: getTaskLogs,
  defaultSearch: {
    task_id: '',
    status: '' as string,
    trigger_type: '' as string,
    start_date: '',
    end_date: '',
  },
  immediate: false,
})

const isArchiveQuery = computed(() => {
  return searchForm.end_date && new Date(searchForm.end_date) < new Date(Date.now() - 90 * 24 * 60 * 60 * 1000)
})

onMounted(async () => {
  try {
    const res = await getTasks({ per_page: 100 })
    tasks.value = res.data || []
  } catch {}
  loadData()
})

function handleDateChange(val: [string, string] | null) {
  if (val) {
    searchForm.start_date = val[0]
    searchForm.end_date = val[1]
  } else {
    searchForm.start_date = ''
    searchForm.end_date = ''
  }
  handleSearch()
}

function getTriggerTypeLabel(type: string) {
  const map: Record<string, string> = {
    schedule: t('scheduler.triggerTypeSchedule'),
    manual: t('scheduler.triggerTypeManual'),
    retry: t('scheduler.triggerTypeRetry'),
  }
  return map[type] || type
}

function getStatusType(status: string) {
  const map: Record<string, any> = { pending: 'info', running: 'warning', success: 'success', failed: 'danger', timeout: 'warning', cancelled: 'info' }
  return map[status] || 'info'
}

function getStatusLabel(status: string) {
  const map: Record<string, string> = {
    pending: t('scheduler.statusPending'),
    running: t('scheduler.statusRunning'),
    success: t('scheduler.statusSuccess'),
    failed: t('scheduler.statusFailed'),
    timeout: t('scheduler.statusTimeout'),
    cancelled: t('scheduler.statusCancelled'),
  }
  return map[status] || status
}

async function handleViewDetail(row: TaskLog) {
  try {
    currentLog.value = await getTaskLog(row.hash_id)
    detailVisible.value = true
  } catch {
    ElMessage.error(t('scheduler.fetchDetailFailed'))
  }
}

function handleViewArchive() {
  ElMessage.info(t('scheduler.archiveInDevelopment'))
}

function getStderrDisplay() {
  if (!currentLog.value?.detail?.stderr_content) {
    return '(' + t('scheduler.empty') + ')'
  }
  return currentLog.value.detail.stderr_content
}

function formatJson(value: any): string {
  if (value === null || value === undefined) return '-'
  if (typeof value === 'string') {
    try {
      return JSON.stringify(JSON.parse(value), null, 2)
    } catch {
      return value || '(' + t('scheduler.empty') + ')'
    }
  }
  try {
    return JSON.stringify(value, null, 2)
  } catch {
    return String(value)
  }
}
</script>

<style scoped>
.task-log-container { padding: 0; }
.search-card { margin-bottom: 16px; }
.search-card :deep(.el-card__body) { padding-bottom: 0; }
.table-card :deep(.el-card__body) { padding-top: 16px; }
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.error-message { color: var(--el-color-danger); }

/* JSON 格式化样式 */
.json-section {
  margin-top: 16px;
}
.json-section-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: var(--el-text-color-primary);
}
.json-viewer {
  width: 100%;
}
.json-viewer :deep(.el-textarea__inner) {
  font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.6;
  background-color: var(--el-fill-color-lighter);
  resize: vertical;
  min-height: 100px;
  max-height: 300px;
  overflow-y: auto;
}
.error-viewer :deep(.el-textarea__inner) {
  color: var(--el-color-danger);
  background-color: var(--el-color-danger-light-9);
  border-color: var(--el-color-danger-light-5);
}
.error-output :deep(.el-textarea__inner) {
  color: var(--el-color-danger);
  background-color: var(--el-color-danger-light-9);
}
</style>
