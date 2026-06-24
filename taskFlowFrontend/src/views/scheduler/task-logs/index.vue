<template>
  <div class="task-log-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item label="任务">
          <el-select v-model="searchForm.task_id" placeholder="请选择任务" clearable filterable style="width: 200px" @clear="handleSearch">
            <el-option v-for="t in tasks" :key="t.hash_id" :label="t.name" :value="t.hash_id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="等待中" value="pending" />
            <el-option label="执行中" value="running" />
            <el-option label="成功" value="success" />
            <el-option label="失败" value="failed" />
            <el-option label="超时" value="timeout" />
            <el-option label="取消" value="cancelled" />
          </el-select>
        </el-form-item>
        <el-form-item label="触发类型">
          <el-select v-model="searchForm.trigger_type" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="调度" value="schedule" />
            <el-option label="手动" value="manual" />
            <el-option label="重试" value="retry" />
          </el-select>
        </el-form-item>
        <el-form-item label="时间范围">
          <el-date-picker v-model="dateRange" type="daterange" range-separator="至" start-placeholder="开始日期" end-placeholder="结束日期" value-format="YYYY-MM-DD" @change="handleDateChange" />
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
          <el-button type="info" @click="handleViewArchive" :disabled="!isArchiveQuery">查看历史归档</el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="task.name" label="任务名称" min-width="150" show-overflow-tooltip />
        <el-table-column label="触发类型" width="100" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ getTriggerTypeLabel(row.trigger_type) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="执行节点" width="120">
          <template #default="{ row }">{{ row.node?.name || '-' }}</template>
        </el-table-column>
        <el-table-column label="开始时间" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.start_time) }}</template>
        </el-table-column>
        <el-table-column label="结束时间" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.end_time) }}</template>
        </el-table-column>
        <el-table-column label="耗时" width="100" align="center">
          <template #default="{ row }">{{ row.duration_ms ? row.duration_ms + 'ms' : '-' }}</template>
        </el-table-column>
        <el-table-column label="操作" width="100" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleViewDetail(row)">详情</el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination :pagination="pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <!-- Detail Dialog -->
    <el-dialog v-model="detailVisible" title="执行详情" width="900px" draggable destroy-on-close>
      <template v-if="currentLog">
        <el-descriptions :column="2" border size="default">
          <el-descriptions-item label="任务名称">{{ currentLog.task?.name }}</el-descriptions-item>
          <el-descriptions-item label="触发ID">{{ currentLog.trigger_id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="执行ID">{{ currentLog.execution_id || '-' }}</el-descriptions-item>
          <el-descriptions-item label="触发类型">
            <el-tag size="small">{{ getTriggerTypeLabel(currentLog.trigger_type) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentLog.status)" size="small">{{ getStatusLabel(currentLog.status) }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="执行节点">{{ currentLog.node?.name || '-' }}</el-descriptions-item>
          <el-descriptions-item label="开始时间">{{ formatDateTime(currentLog.start_time) }}</el-descriptions-item>
          <el-descriptions-item label="结束时间">{{ formatDateTime(currentLog.end_time) }}</el-descriptions-item>
          <el-descriptions-item label="耗时">{{ currentLog.duration_ms ? currentLog.duration_ms + ' ms' : '-' }}</el-descriptions-item>
          <el-descriptions-item label="重试次数">{{ currentLog.retry_count ?? 0 }}</el-descriptions-item>
          <el-descriptions-item label="错误信息" :span="2">
            <span class="error-message" v-if="currentLog.error_message">{{ currentLog.error_message }}</span>
            <span v-else>-</span>
          </el-descriptions-item>
        </el-descriptions>

        <!-- 响应摘要 -->
        <div class="json-section">
          <div class="json-section-title">响应摘要</div>
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
          <div class="json-section-title">执行输出</div>
          <el-tabs>
            <el-tab-pane label="标准输出">
              <el-input
                type="textarea"
                :model-value="formatJson(currentLog.detail.stdout_content)"
                :rows="8"
                readonly
                class="json-viewer"
              />
            </el-tab-pane>
            <el-tab-pane label="错误输出">
              <el-input
                type="textarea"
                :model-value="currentLog.detail.stderr_content || '(空)'"
                :rows="8"
                readonly
                class="json-viewer"
                :class="{ 'error-output': currentLog.detail.stderr_content }"
              />
            </el-tab-pane>
            <el-tab-pane label="请求快照">
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
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
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
  const map: Record<string, string> = { schedule: '调度', manual: '手动', retry: '重试' }
  return map[type] || type
}

function getStatusType(status: string) {
  const map: Record<string, any> = { pending: 'info', running: 'warning', success: 'success', failed: 'danger', timeout: 'warning', cancelled: 'info' }
  return map[status] || 'info'
}

function getStatusLabel(status: string) {
  const map: Record<string, string> = { pending: '等待中', running: '执行中', success: '成功', failed: '失败', timeout: '超时', cancelled: '取消' }
  return map[status] || status
}

async function handleViewDetail(row: TaskLog) {
  try {
    currentLog.value = await getTaskLog(row.hash_id)
    detailVisible.value = true
  } catch {
    ElMessage.error('获取详情失败')
  }
}

function handleViewArchive() {
  ElMessage.info('历史归档查询功能开发中')
}

function formatJson(value: any): string {
  if (value === null || value === undefined) return '-'
  if (typeof value === 'string') {
    try {
      return JSON.stringify(JSON.parse(value), null, 2)
    } catch {
      return value || '(空)'
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
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 8px;
  color: var(--el-text-color-primary);
}
.json-viewer :deep(.el-textarea__inner) {
  font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
  font-size: 12px;
  line-height: 1.6;
  background-color: var(--el-fill-color-lighter);
  resize: vertical;
  min-height: 120px;
}
.error-output :deep(.el-textarea__inner) {
  color: var(--el-color-danger);
  background-color: var(--el-color-danger-light-9);
}
</style>
