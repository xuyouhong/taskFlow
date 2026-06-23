<template>
  <div class="dashboard-container">
    <!-- Stat Cards -->
    <div class="quick-stats">
      <el-row :gutter="20">
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover">
            <div class="stat-item">
              <div class="stat-icon" style="background-color: #409eff">
                <el-icon><User /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-title">{{ t('dashboard.totalUsers') }}</div>
                <div class="stat-value">{{ stats.user_stats?.total || 0 }}</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover">
            <div class="stat-item">
              <div class="stat-icon" style="background-color: #67c23a">
                <el-icon><UserFilled /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-title">{{ t('dashboard.activeUsers') }}</div>
                <div class="stat-value">{{ stats.user_stats?.active || 0 }}</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover">
            <div class="stat-item">
              <div class="stat-icon" style="background-color: #e6a23c">
                <el-icon><Monitor /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-title">{{ t('dashboard.todayLogin') }}</div>
                <div class="stat-value">{{ stats.user_stats?.today_login || 0 }}</div>
              </div>
            </div>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover">
            <div class="stat-item">
              <div class="stat-icon" style="background-color: #f56c6c">
                <el-icon><Document /></el-icon>
              </div>
              <div class="stat-content">
                <div class="stat-title">{{ t('dashboard.operationLogs') }}</div>
                <div class="stat-value">{{ stats.operation_stats?.total || 0 }}</div>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Charts -->
    <div class="chart-area">
      <el-row :gutter="20">
        <el-col :xs="24" :md="16">
          <el-card shadow="hover">
            <template #header>
              <div class="card-header">
                <span>{{ t('dashboard.loginStats') }}</span>
                <div class="header-extra">
                  <el-select v-model="chartDays" @change="loadChartData" size="small">
                    <el-option :label="t('dashboard.last7Days')" value="7" />
                    <el-option :label="t('dashboard.last30Days')" value="30" />
                    <el-option :label="t('dashboard.last90Days')" value="90" />
                  </el-select>
                </div>
              </div>
            </template>
            <div ref="loginChartRef" style="width: 100%; height: 300px"></div>
          </el-card>
        </el-col>
        <el-col :xs="24" :md="8">
          <el-card shadow="hover">
            <template #header>
              <span>{{ t('dashboard.loginSuccessRate') }}</span>
            </template>
            <div ref="successRateChartRef" style="width: 100%; height: 300px"></div>
          </el-card>
        </el-col>
      </el-row>
    </div>

    <!-- Recent Logins -->
    <div class="recent-logins">
      <el-card shadow="hover">
        <template #header>
          <span>{{ t('dashboard.recentLogins') }}</span>
        </template>
        <el-table :data="stats.recent_logins" style="width: 100%">
          <el-table-column prop="username" :label="t('dashboard.user')" width="200">
            <template #default="{ row }">
              <div class="user-cell">
                <el-avatar :size="28" :src="handleImageUrl(row.user?.avatar)" class="user-avatar">
                  {{ (row.user?.real_name || row.user?.username || row.username)?.charAt(0)?.toUpperCase() }}
                </el-avatar>
                <div class="user-info">
                  <div class="user-name">{{ row.user?.real_name || row.user?.username || row.username }}</div>
                  <div class="user-email">{{ row.user?.email || '' }}</div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="ip" :label="t('dashboard.ipAddress')" width="130" />
          <el-table-column :label="t('dashboard.location')" min-width="150" show-overflow-tooltip>
            <template #default="{ row }">
              {{ [row.country, row.region, row.city].filter(Boolean).join(' / ') || '-' }}
            </template>
          </el-table-column>
          <el-table-column prop="browser" :label="t('dashboard.browser')" width="120" />
          <el-table-column prop="os" :label="t('dashboard.os')" width="120" />
          <el-table-column prop="device" :label="t('dashboard.device')" width="100" />
          <el-table-column prop="login_at" :label="t('dashboard.loginTime')" width="170">
            <template #default="{ row }">
              {{ formatDateTime(row.login_at) }}
            </template>
          </el-table-column>
          <el-table-column prop="logout_at" :label="t('dashboard.logoutAt')" width="170">
            <template #default="{ row }">
              {{ row.logout_at ? formatDateTime(row.logout_at) : '-' }}
            </template>
          </el-table-column>
          <el-table-column prop="online_duration" :label="t('dashboard.onlineDuration')" width="110">
            <template #default="{ row }">
              {{ formatDuration(row.online_duration) }}
            </template>
          </el-table-column>
          <el-table-column prop="status" :label="t('dashboard.status')" width="80">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
                {{ row.status === 1 ? t('dashboard.success') : t('dashboard.failed') }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="message" :label="t('dashboard.message')" min-width="120" show-overflow-tooltip />
        </el-table>
      </el-card>
    </div>

    <!-- System Info -->
    <div class="system-info">
      <el-card shadow="hover">
        <template #header>
          <span>{{ t('dashboard.systemInfo') }}</span>
        </template>
        <el-descriptions :column="1" border>
          <el-descriptions-item :label="t('dashboard.phpVersion')">{{
            stats.system_info?.php_version || '-'
          }}</el-descriptions-item>
          <el-descriptions-item :label="t('dashboard.laravelVersion')">{{
            stats.system_info?.laravel_version || '-'
          }}</el-descriptions-item>
          <el-descriptions-item :label="t('dashboard.serverSoftware')">{{
            stats.system_info?.server_software || '-'
          }}</el-descriptions-item>
          <el-descriptions-item :label="t('dashboard.database')">{{
            stats.system_info?.database_connection || '-'
          }}</el-descriptions-item>
          <el-descriptions-item :label="t('dashboard.timezone')">{{
            stats.system_info?.timezone || '-'
          }}</el-descriptions-item>
          <el-descriptions-item :label="t('dashboard.uploadLimit')">{{
            stats.system_info?.upload_max_filesize || '-'
          }}</el-descriptions-item>
          <el-descriptions-item :label="t('dashboard.memoryLimit')">{{
            stats.system_info?.memory_limit || '-'
          }}</el-descriptions-item>
        </el-descriptions>
      </el-card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import * as echarts from 'echarts'
import { User, UserFilled, Monitor, Document } from '@element-plus/icons-vue'
import { getDashboard, getChartData } from '@/api/dashboard'
import type { DashboardStats } from '@/types/api'
import { formatDateTime, formatDuration } from '@/utils/format'
import { handleImageUrl } from '@/utils/imageUrl'
import { useAppStore } from '@/stores/app'

const { t } = useI18n()
const appStore = useAppStore()

const loginChartRef = ref<HTMLElement>()
const successRateChartRef = ref<HTMLElement>()

let loginChart: echarts.ECharts | null = null
let successRateChart: echarts.ECharts | null = null

const stats = ref<Partial<DashboardStats>>({})
const chartDays = ref('7')

async function loadDashboardData() {
  try {
    const data = await getDashboard() as any
    if (data?.recent_logins && Array.isArray(data.recent_logins)) {
      data.recent_logins = data.recent_logins.map((log: any) => {
        if (log.message) {
          log.message = log.message.replace(/\[(0x[0-9a-fA-F]+\s*)+\]/g, '').trim()
          if (!log.message) {
            log.message = log.status === 1 ? t('dashboard.success') : t('dashboard.failed')
          }
        }
        return log
      })
    }
    stats.value = data || {}
  } catch {
    // ignore
  }
}

async function loadChartData() {
  try {
    const data = await getChartData({
      type: 'login',
      days: parseInt(chartDays.value),
    }) as any

    const chartData = Array.isArray(data) ? data : []

    // Login trend chart (combo: line + bar)
    if (loginChart && chartData.length > 0) {
      loginChart.setOption({
        tooltip: {
          trigger: 'axis',
          axisPointer: { type: 'cross', crossStyle: { color: '#999' } },
        },
        legend: {
          data: [t('dashboard.loginCount'), t('dashboard.activeUsers')],
          bottom: 0,
          left: 'center',
        },
        grid: { left: '3%', right: '4%', bottom: '40px', top: '10px', containLabel: true },
        xAxis: { type: 'category', data: chartData.map((d: any) => d.date) },
        yAxis: [
          { type: 'value', name: t('dashboard.loginCount'), position: 'left' },
          { type: 'value', name: t('dashboard.activeUsers'), position: 'right' },
        ],
        series: [
          {
            name: t('dashboard.loginCount'),
            type: 'line',
            data: chartData.map((d: any) => d.total),
            smooth: true,
            itemStyle: { color: '#409eff' },
          },
          {
            name: t('dashboard.activeUsers'),
            type: 'bar',
            yAxisIndex: 1,
            data: chartData.map((d: any) => d.active_users || 0),
            itemStyle: { color: '#67c23a' },
          },
        ],
      })
    }

    // Success rate pie chart
    if (successRateChart && stats.value.login_stats) {
      const success = Number(stats.value.login_stats.success) || 0
      const failed = Number(stats.value.login_stats.failed) || 0
      successRateChart.setOption({
        tooltip: {
          trigger: 'item',
          formatter: '{a} <br/>{b}: {c} ({d}%)',
        },
        legend: { orient: 'vertical', left: 'left' },
        series: [
          {
            name: t('dashboard.loginStats'),
            type: 'pie',
            radius: '50%',
            data: [
              { value: success, name: t('dashboard.success') },
              { value: failed, name: t('dashboard.failed') },
            ],
            emphasis: {
              itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.5)' },
            },
            label: { formatter: '{b}: {c} ({d}%)' },
          },
        ],
      })
    }
  } catch {
    // ignore
  }
}

function initCharts() {
  nextTick(() => {
    if (loginChartRef.value) {
      loginChart = echarts.init(loginChartRef.value, undefined, { passive: true })
    }
    if (successRateChartRef.value) {
      successRateChart = echarts.init(successRateChartRef.value, undefined, { passive: true })
    }
  })
}

function handleResize() {
  loginChart?.resize()
  successRateChart?.resize()
}

async function reloadAll() {
  await loadDashboardData()
  await loadChartData()
}

watch(() => appStore.language, () => { reloadAll() })

onMounted(async () => {
  initCharts()
  await nextTick()
  await reloadAll()
  window.addEventListener('resize', handleResize)
})

onBeforeUnmount(() => {
  loginChart?.dispose()
  successRateChart?.dispose()
  window.removeEventListener('resize', handleResize)
})
</script>

<style lang="scss" scoped>
.dashboard-container {
  padding: 20px;
  height: calc(100vh - var(--navbar-height, 60px) - var(--tags-view-height, 34px) - 40px);
  overflow-y: auto;
  box-sizing: border-box;

  .quick-stats {
    margin-bottom: 20px;

    .stat-item {
      display: flex;
      align-items: center;

      .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;

        .el-icon {
          font-size: 24px;
          color: #fff;
        }
      }

      .stat-content {
        .stat-title {
          font-size: 14px;
          color: var(--el-text-color-secondary);
          margin-bottom: 5px;
        }

        .stat-value {
          font-size: 24px;
          font-weight: bold;
          color: var(--el-text-color-primary);
        }
      }
    }
  }

  .chart-area {
    margin-bottom: 20px;

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  }

  .recent-logins {
    margin-bottom: 20px;
  }

  .system-info {
    margin-bottom: 20px;
  }
}

.dashboard-container::-webkit-scrollbar {
  width: 6px;
}
.dashboard-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}
.dashboard-container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}
.dashboard-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
