<template>
  <div class="login-logs-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('log.username')">
          <el-input
            v-model="searchForm.username"
            :placeholder="t('log.usernamePlaceholder')"
            clearable
            style="width: 180px"
            @keyup.enter="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('log.status')">
          <el-select
            v-model="searchForm.status"
            clearable
            style="width: 140px"
          >
            <el-option :label="t('common.all')" value="" />
            <el-option :label="t('log.loginSuccess')" :value="1" />
            <el-option :label="t('log.loginFailed')" :value="0" />
          </el-select>
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

    <!-- Toolbar -->
    <el-card shadow="never" class="table-card">
      <template #header>
        <div class="toolbar">
          <div class="toolbar-left">
            <el-button
              v-permission="['login-logs.batch-destroy']"
              type="danger"
              :icon="Delete"
              :disabled="selectedRows.length === 0"
              @click="handleBatchDelete"
            >
              {{ t('common.batchDelete') }}
            </el-button>
          </div>
          <div class="toolbar-right">
            <el-button
              v-permission="['login-logs.statistics']"
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

      <!-- Table -->
      <el-table
        v-loading="loading"
        :data="tableData"
        border
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" align="center" />
        <el-table-column prop="username" :label="t('log.username')" width="120" />
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
        <el-table-column prop="ip" :label="t('log.ip')" width="140" />
        <el-table-column prop="browser" :label="t('log.browser')" width="120" show-overflow-tooltip />
        <el-table-column prop="os" :label="t('log.os')" width="120" show-overflow-tooltip />
        <el-table-column prop="device" :label="t('log.device')" width="100" show-overflow-tooltip />
        <el-table-column :label="t('log.location')" min-width="160" show-overflow-tooltip>
          <template #default="{ row }">
            {{ [row.country, row.region, row.city].filter(Boolean).join(' / ') || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="login_at" :label="t('log.loginAt')" width="170">
          <template #default="{ row }">
            {{ formatDateTime(row.login_at) }}
          </template>
        </el-table-column>
        <el-table-column prop="logout_at" :label="t('log.logoutAt')" width="170">
          <template #default="{ row }">
            {{ row.logout_at ? formatDateTime(row.logout_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="online_duration" :label="t('log.onlineDuration')" width="110">
          <template #default="{ row }">
            {{ formatDuration(row.online_duration) }}
          </template>
        </el-table-column>
        <el-table-column prop="status" :label="t('log.status')" width="90" align="center">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1" type="success" size="small">
              {{ t('log.loginSuccess') }}
            </el-tag>
            <el-tag v-else type="danger" size="small">
              {{ t('log.loginFailed') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="message" :label="t('log.message')" min-width="150" show-overflow-tooltip />
        <el-table-column :label="t('common.actions')" width="140" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleView(row)">
              {{ t('common.view') }}
            </el-button>
            <el-button
              v-permission="['login-logs.destroy']"
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
      width="700px"
      draggable
      destroy-on-close
    >
      <el-descriptions
        v-if="currentRow"
        :column="2"
        border
        size="default"
      >
        <el-descriptions-item label="ID">{{ currentRow.hash_id }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.username')">{{ currentRow.username }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.realname')">
          <div class="user-cell">
            <el-avatar :size="32" :src="handleImageUrl(currentRow.user?.avatar)" class="user-avatar">
              {{ getUserAvatarText(currentRow) }}
            </el-avatar>
            <div class="user-info">
              <div class="user-name">{{ getUserDisplayName(currentRow) }}</div>
              <div class="user-email">{{ getUserEmail(currentRow) }}</div>
            </div>
          </div>
        </el-descriptions-item>
        <el-descriptions-item :label="t('log.ip')">{{ currentRow.ip }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.browser')">{{ currentRow.browser || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.os')">{{ currentRow.os || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.device')">{{ currentRow.device || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.location')">
          {{ [currentRow.country, currentRow.region, currentRow.city].filter(Boolean).join(' / ') || '-' }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('log.status')">
          <el-tag v-if="currentRow.status === 1" type="success" size="small">
            {{ t('log.loginSuccess') }}
          </el-tag>
          <el-tag v-else type="danger" size="small">
            {{ t('log.loginFailed') }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item :label="t('log.message')">{{ currentRow.message || '-' }}</el-descriptions-item>
        <el-descriptions-item :label="t('log.loginAt')">
          {{ formatDateTime(currentRow.login_at) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('log.logoutAt')">
          {{ formatDateTime(currentRow.logout_at) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('log.onlineDuration')">
          {{ formatDuration(currentRow.online_duration) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('common.created_at')">
          {{ formatDateTime(currentRow.created_at) }}
        </el-descriptions-item>
        <el-descriptions-item :label="t('common.updated_at')">
          {{ formatDateTime(currentRow.updated_at) }}
        </el-descriptions-item>
        <el-descriptions-item label="User Agent" :span="2">
          {{ currentRow.user_agent || '-' }}
        </el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="detailVisible = false">{{ t('common.close') }}</el-button>
      </template>
    </el-dialog>

    <!-- Statistics Dialog -->
    <el-dialog
      v-model="statsVisible"
      :title="t('log.statistics')"
      width="800px"
      draggable
      destroy-on-close
      @opened="renderStatsChart"
    >
      <div style="display: flex; justify-content: flex-end; margin-bottom: 12px">
        <el-radio-group v-model="statsDays" size="small" @change="loadStatistics">
          <el-radio-button :value="7">{{ t('dashboard.days7') }}</el-radio-button>
          <el-radio-button :value="30">{{ t('dashboard.days30') }}</el-radio-button>
        </el-radio-group>
      </div>
      <div ref="statsChartRef" style="height: 400px" />
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
import { Search, Refresh, Delete, DataAnalysis } from '@element-plus/icons-vue'
import * as echarts from 'echarts'
import { useTable } from '@/composables/useTable'
import { getLoginLogs, deleteLoginLog, batchDeleteLoginLogs, getLoginLogStatistics } from '@/api/log'
import { formatDateTime, formatDuration } from '@/utils/format'
import { handleImageUrl } from '@/utils/imageUrl'
import Pagination from '@/components/Pagination/index.vue'
import type { LoginLog, LoginLogStatItem } from '@/types/api'

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
} = useTable<LoginLog>({
  fetchApi: getLoginLogs,
  defaultSearch: {
    username: '',
    status: '',
    start_date: '',
    end_date: '',
  },
})

// Detail dialog
const detailVisible = ref(false)
const currentRow = ref<LoginLog | null>(null)

function handleView(row: LoginLog) {
  currentRow.value = row
  detailVisible.value = true
}

function getUserDisplayName(row: LoginLog): string {
  return row.user?.real_name || row.username || t('common.unknown')
}

function getUserEmail(row: LoginLog): string {
  return row.user?.email || t('common.unknown')
}

function getUserAvatarText(row: LoginLog): string {
  return (row.user?.real_name || row.user?.username || row.username)?.charAt(0)?.toUpperCase() || ''
}

// Delete
async function handleDelete(row: LoginLog) {
  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), {
      type: 'warning',
    })
    await deleteLoginLog(row.hash_id)
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

// Batch delete
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
    await batchDeleteLoginLogs({ ids })
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

// Statistics
const statsVisible = ref(false)
const statsDays = ref(7)
const statsChartRef = ref<HTMLElement>()
const statsData = ref<LoginLogStatItem[]>([])
let statsChart: echarts.ECharts | null = null

function openStatistics() {
  statsVisible.value = true
  statsDays.value = 7
  loadStatistics()
}

async function loadStatistics() {
  try {
    const res = await getLoginLogStatistics({ days: statsDays.value })
    statsData.value = Array.isArray(res) ? res : []
    await nextTick()
    renderStatsChart()
  } catch {
    // ignore
  }
}

function renderStatsChart() {
  if (!statsChartRef.value) return
  if (!statsChart) {
    statsChart = echarts.init(statsChartRef.value)
  }
  const data = statsData.value
  statsChart.setOption({
    tooltip: { trigger: 'axis' },
    legend: {
      data: [t('dashboard.totalLogins'), t('dashboard.successLogins'), t('dashboard.failedLogins')],
      bottom: 0,
      left: 'center',
    },
    grid: { left: '3%', right: '4%', bottom: '12%', top: '10px', containLabel: true },
    xAxis: { type: 'category', data: data.map((d) => d.date) },
    yAxis: { type: 'value' },
    series: [
      {
        name: t('dashboard.totalLogins'),
        type: 'bar',
        data: data.map((d) => d.total_logins),
        itemStyle: { color: '#409EFF' },
      },
      {
        name: t('dashboard.successLogins'),
        type: 'bar',
        data: data.map((d) => d.success_logins),
        itemStyle: { color: '#67C23A' },
      },
      {
        name: t('dashboard.failedLogins'),
        type: 'bar',
        data: data.map((d) => d.failed_logins),
        itemStyle: { color: '#F56C6C' },
      },
    ],
  })
}

function handleResize() {
  statsChart?.resize()
}

window.addEventListener('resize', handleResize)

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  statsChart?.dispose()
})
</script>

<style scoped>
.login-logs-container {
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
