<template>
  <div class="operation-logs-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('log.username')">
          <el-input
            v-model="searchForm.username"
            :placeholder="t('log.usernamePlaceholder')"
            clearable
            style="width: 160px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('log.method')">
          <el-select v-model="searchForm.method" clearable style="width: 130px">
            <el-option :label="t('log.methodAll')" value="" />
            <el-option :label="t('log.methodGET')" value="GET" />
            <el-option :label="t('log.methodPOST')" value="POST" />
            <el-option :label="t('log.methodPUT')" value="PUT" />
            <el-option :label="t('log.methodDELETE')" value="DELETE" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('log.path')">
          <el-input
            v-model="searchForm.path"
            :placeholder="t('log.pathPlaceholder')"
            clearable
            style="width: 200px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('log.startDate')">
          <el-date-picker
            v-model="searchForm.start_date"
            type="date"
            value-format="YYYY-MM-DD"
            style="width: 160px"
          />
        </el-form-item>
        <el-form-item :label="t('log.endDate')">
          <el-date-picker
            v-model="searchForm.end_date"
            type="date"
            value-format="YYYY-MM-DD"
            style="width: 160px"
          />
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
              v-permission="['operation-logs.batch-destroy']"
              type="danger"
              :icon="Delete"
              :disabled="selectedRows.length === 0"
              @click="handleBatchDelete"
            >
              {{ t('common.batchDelete') }}
            </el-button>
            <el-button
              v-permission="['operation-logs.clean']"
              type="warning"
              :icon="DeleteFilled"
              @click="openCleanDialog"
            >
              {{ t('log.cleanLogs') }}
            </el-button>
          </div>
          <div class="toolbar-right">
            <el-button
              v-permission="['operation-logs.statistics']"
              :icon="DataAnalysis" 
              @click="openStatistics">
              {{ t('log.statistics') }}
            </el-button>
            <el-button :icon="Refresh" @click="loadData">
              {{ t('common.refresh') }}
            </el-button>
          </div>
        </div>
      </template>

      <el-table
        v-loading="loading"
        :data="tableData"
        border
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" align="center" />
        <el-table-column prop="username" :label="t('log.username')" width="110" />
        <el-table-column :label="t('log.realname')" width="220">
          <template #default="{ row }">
            <div class="user-cell">
              <el-avatar :size="28" :src="handleImageUrl(row.user?.avatar)" class="user-avatar">
                {{ getUserAvatarText(row) }}
              </el-avatar>
              <div class="user-info">
                <div class="user-name">{{ getUserDisplayName(row) }}</div>
                <div class="user-email">{{ getUserEmail(row) }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="method" :label="t('log.method')" width="100" align="center">
          <template #default="{ row }">
            <el-tag
              :type="methodTagType(row.method)"
              size="small"
              effect="dark"
            >
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="path" :label="t('log.path')" min-width="220" show-overflow-tooltip />
        <el-table-column prop="status_code" :label="t('log.statusCode')" width="100" align="center">
          <template #default="{ row }">
            <el-tag
              :type="row.status_code >= 200 && row.status_code < 300 ? 'success' : row.status_code >= 400 ? 'danger' : 'warning'"
              size="small"
            >
              {{ row.status_code }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="duration" :label="t('log.duration')" width="100" align="right">
          <template #default="{ row }">
            {{ row.duration != null ? row.duration + ' ms' : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="ip" :label="t('log.ip')" width="140" />
        <el-table-column prop="operated_at" :label="t('log.operatedAt')" width="170">
          <template #default="{ row }">
            {{ formatDateTime(row.operated_at) }}
          </template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="140" align="center" fixed="right">
          <template #default="{ row }">
            <el-button 
              v-permission="['operation-logs.show']" 
              type="primary" 
              link size="small" 
              @click="handleView(row)"
            >
              {{ t('common.view') }}
            </el-button>
            <el-button
              v-permission="['operation-logs.destroy']"
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

    <!-- Detail Dialog -->
    <el-dialog
      v-model="detailVisible"
      :title="t('log.detail')"
      width="780px"
      draggable
      destroy-on-close
    >
      <template v-if="currentRow">
        <el-descriptions :column="2" border size="default">
          <el-descriptions-item label="ID">{{ currentRow.hash_id }}</el-descriptions-item>
          <el-descriptions-item :label="t('log.username')">{{ currentRow.username }}</el-descriptions-item>
          <el-descriptions-item :label="t('log.realname')">
            <div class="user-cell" v-if="currentRow.user">
              <el-avatar :size="32" :src="handleImageUrl(currentRow.user.avatar)" class="user-avatar">
                {{ (currentRow.user.real_name || currentRow.user.username)?.charAt(0)?.toUpperCase() }}
              </el-avatar>
              <div class="user-info">
                <div class="user-name">{{ currentRow.user.real_name || currentRow.user.username }}</div>
                <div class="user-email">{{ currentRow.user.email }}</div>
              </div>
            </div>
            <div class="user-cell" v-else>
              <el-avatar :size="32" class="user-avatar">
                {{ currentRow.username?.charAt(0)?.toUpperCase() }}
              </el-avatar>
              <div class="user-info">
                <div class="user-name">{{ currentRow.username }}</div>
                <div class="user-email">{{ t('common.unknown') }}</div>
              </div>
            </div>
          </el-descriptions-item>
          <el-descriptions-item :label="t('log.method')">
            <el-tag :type="methodTagType(currentRow.method)" size="small" effect="dark">
              {{ currentRow.method }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item :label="t('log.statusCode')">
            <el-tag
              :type="currentRow.status_code >= 200 && currentRow.status_code < 300 ? 'success' : 'danger'"
              size="small"
            >
              {{ currentRow.status_code }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item :label="t('log.path')" :span="2">
            {{ currentRow.path }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('log.ip')">{{ currentRow.ip }}</el-descriptions-item>
          <el-descriptions-item :label="t('log.duration')">
            {{ currentRow.duration != null ? currentRow.duration + ' ms' : '-' }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('log.operatedAt')">
            {{ formatDateTime(currentRow.operated_at) }}
          </el-descriptions-item>
          <el-descriptions-item :label="t('common.created_at')">
            {{ formatDateTime(currentRow.created_at) }}
          </el-descriptions-item>
          <el-descriptions-item label="User Agent" :span="2">
            {{ currentRow.user_agent || '-' }}
          </el-descriptions-item>
        </el-descriptions>

        <!-- Params -->
        <div class="json-section">
          <div class="json-section-title">{{ t('log.params') }}</div>
          <el-input
            type="textarea"
            :model-value="formatJson(currentRow.params)"
            :rows="6"
            readonly
            class="json-viewer"
          />
        </div>

        <!-- Response -->
        <div class="json-section">
          <div class="json-section-title">{{ t('log.response') }}</div>
          <el-input
            type="textarea"
            :model-value="formatJson(currentRow.response)"
            :rows="6"
            readonly
            class="json-viewer"
          />
        </div>
      </template>

      <template #footer>
        <el-button @click="detailVisible = false">{{ t('common.close') }}</el-button>
      </template>
    </el-dialog>

    <!-- Clean Dialog -->
    <el-dialog
      v-model="cleanVisible"
      :title="t('log.cleanLogs')"
      width="440px"
      draggable
      destroy-on-close
    >
      <el-form label-width="160px">
        <el-form-item :label="t('log.cleanLogsPlaceholder')">
          <el-input-number v-model="cleanDays" :min="1" :max="365" />
          <span style="margin-left: 8px">{{ t('common.days') }}</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="cleanVisible = false">{{ t('common.cancel') }}</el-button>
        <el-button type="primary" :loading="cleanLoading" @click="handleClean">
          {{ t('common.confirm') }}
        </el-button>
      </template>
    </el-dialog>

    <!-- Statistics Dialog -->
    <el-dialog
      v-model="statsVisible"
      :title="t('log.statistics')"
      width="900px"
      draggable
      destroy-on-close
      @opened="renderStatsCharts"
    >
      <div style="display: flex; justify-content: flex-end; margin-bottom: 12px">
        <el-radio-group v-model="statsDays" size="small" @change="loadStatistics">
          <el-radio-button :value="7">{{ t('dashboard.days7') }}</el-radio-button>
          <el-radio-button :value="30">{{ t('dashboard.days30') }}</el-radio-button>
        </el-radio-group>
      </div>
      <el-row :gutter="16">
        <el-col :span="14">
          <div ref="dailyChartRef" style="height: 380px" />
        </el-col>
        <el-col :span="10">
          <div ref="methodChartRef" style="height: 380px" />
        </el-col>
      </el-row>
      <template #footer>
        <el-button @click="statsVisible = false">{{ t('common.close') }}</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Search, Refresh, Delete, DataAnalysis, DeleteFilled } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { useTable } from '@/composables/useTable'
import {
  getOperationLogs,
  deleteOperationLog,
  batchDeleteOperationLogs,
  cleanOperationLogs,
  getOperationLogStatistics,
} from '@/api/log'
import { formatDateTime } from '@/utils/format'
import Pagination from '@/components/Pagination/index.vue'
import type { OperationLog, OperationLogStats } from '@/types/api'
import { handleImageUrl } from '@/utils/imageUrl'

const { t } = useI18n()

const {
  tableData,
  loading,
  selectedRows,
  pagination,
  searchForm,
  loadData,
  handleSearch,
  handleReset,
  handleSizeChange,
  handleCurrentChange,
  handleSelectionChange,
} = useTable<OperationLog>({
  fetchApi: getOperationLogs,
  defaultSearch: {
    username: '',
    method: '',
    path: '',
    start_date: '',
    end_date: '',
  },
})

// ---------- Method tag color ----------
function methodTagType(method: string): '' | 'success' | 'warning' | 'danger' | 'info' {
  const map: Record<string, '' | 'success' | 'warning' | 'danger' | 'info'> = {
    GET: 'info',
    POST: 'success',
    PUT: 'warning',
    DELETE: 'danger',
  }
  return map[method] || ''
}

// ---------- Detail dialog ----------
const detailVisible = ref(false)
const currentRow = ref<OperationLog | null>(null)

function handleView(row: OperationLog) {
  currentRow.value = row
  detailVisible.value = true
}

function getUserDisplayName(row: OperationLog): string {
  return row.user?.real_name || row.username || t('common.unknown')
}

function getUserEmail(row: OperationLog): string {
  return row.user?.email || t('common.unknown')
}

function getUserAvatarText(row: OperationLog): string {
  return (row.user?.real_name || row.user?.username || row.username)?.charAt(0)?.toUpperCase() || ''
}

function getStatusCodeTagType(code: number): 'success' | 'warning' | 'danger' {
  if (code >= 200 && code < 300) return 'success'
  if (code >= 400) return 'danger'
  return 'warning'
}

function formatDuration(duration: number | null | undefined): string {
  return duration != null ? duration + ' ms' : '-'
}

function formatJson(value: any): string {
  if (value === null || value === undefined) return '-'
  if (typeof value === 'string') {
    try {
      return JSON.stringify(JSON.parse(value), null, 2)
    } catch {
      return value
    }
  }
  try {
    return JSON.stringify(value, null, 2)
  } catch {
    return String(value)
  }
}

// ---------- Delete ----------
async function handleDelete(row: OperationLog) {
  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), { type: 'warning' })
    await deleteOperationLog(row.hash_id)
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

async function handleBatchDelete() {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('common.selectAtLeast'))
    return
  }
  try {
    await ElMessageBox.confirm(
      t('common.batchDeleteConfirm', { count: selectedRows.value.length }),
      t('common.tip'),
      { type: 'warning' }
    )
    const ids = selectedRows.value.map((row) => row.hash_id)
    await batchDeleteOperationLogs({ ids })
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

// ---------- Clean logs ----------
const cleanVisible = ref(false)
const cleanDays = ref(30)
const cleanLoading = ref(false)

function openCleanDialog() {
  cleanDays.value = 30
  cleanVisible.value = true
}

async function handleClean() {
  try {
    await ElMessageBox.confirm(
      t('log.cleanLogsConfirm', { days: cleanDays.value }),
      t('common.tip'),
      { type: 'warning' }
    )
  } catch {
    return
  }
  cleanLoading.value = true
  try {
    await cleanOperationLogs({ days: cleanDays.value })
    ElMessage.success(t('log.cleanLogsSuccess'))
    cleanVisible.value = false
    loadData()
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  } finally {
    cleanLoading.value = false
  }
}

// ---------- Statistics ----------
const statsVisible = ref(false)
const statsDays = ref(7)
const statsData = ref<OperationLogStats | null>(null)
const dailyChartRef = ref<HTMLElement>()
const methodChartRef = ref<HTMLElement>()
let dailyChart: echarts.ECharts | null = null
let methodChart: echarts.ECharts | null = null

function openStatistics() {
  statsVisible.value = true
  statsDays.value = 7
  loadStatistics()
}

async function loadStatistics() {
  try {
    const res = await getOperationLogStatistics({ days: statsDays.value })
    statsData.value = (res as any) || null
    await nextTick()
    renderStatsCharts()
  } catch {
    // ignore
  }
}

function renderStatsCharts() {
  const data = statsData.value
  if (!data) return

  // Daily operations chart
  if (dailyChartRef.value) {
    if (!dailyChart) {
      dailyChart = echarts.init(dailyChartRef.value)
    }
    const daily = data.daily_stats || []
    dailyChart.setOption({
      title: { text: t('dashboard.operationTrend'), left: 'center', textStyle: { fontSize: 14 } },
      tooltip: { trigger: 'axis' },
      legend: { data: [t('dashboard.totalOperations'), t('dashboard.activeOperationUsers')], bottom: 0 },
      grid: { left: '3%', right: '4%', bottom: '12%', containLabel: true },
      xAxis: { type: 'category', data: daily.map((d) => d.date), axisLabel: { rotate: 30 } },
      yAxis: { type: 'value' },
      series: [
        {
          name: t('dashboard.totalOperations'),
          type: 'bar',
          data: daily.map((d) => d.total_operations),
          itemStyle: { color: '#409EFF' },
        },
        {
          name: t('dashboard.activeOperationUsers'),
          type: 'bar',
          data: daily.map((d) => d.active_users),
          itemStyle: { color: '#67C23A' },
        },
      ],
    })
  }

  // Method distribution chart
  if (methodChartRef.value) {
    if (!methodChart) {
      methodChart = echarts.init(methodChartRef.value)
    }
    const methods = data.method_stats || []
    methodChart.setOption({
      title: { text: t('log.method'), left: 'center', textStyle: { fontSize: 14 } },
      tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
      legend: { bottom: 0 },
      series: [
        {
          type: 'pie',
          radius: ['40%', '65%'],
          center: ['50%', '48%'],
          data: methods.map((m) => ({ name: m.method, value: m.count })),
          label: { formatter: '{b}\n{d}%' },
          emphasis: {
            itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' },
          },
        },
      ],
    })
  }
}

function handleResize() {
  dailyChart?.resize()
  methodChart?.resize()
}

window.addEventListener('resize', handleResize)

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  dailyChart?.dispose()
  methodChart?.dispose()
})
</script>

<style scoped>
.operation-logs-container {
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
}
</style>
