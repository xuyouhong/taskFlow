<template>
  <div class="task-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('scheduler.project')">
          <el-select v-model="searchForm.project_id" :placeholder="t('scheduler.projectPlaceholder')" clearable style="width: 150px" @clear="handleSearch">
            <el-option v-for="p in projects" :key="p.hash_id" :label="p.name" :value="p.hash_id" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('scheduler.taskName')">
          <el-input v-model="searchForm.keyword" :placeholder="t('scheduler.taskNamePlaceholder')" clearable @clear="handleSearch" />
        </el-form-item>
        <el-form-item :label="t('scheduler.executor')">
          <el-select v-model="searchForm.executor_type" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="HTTP" value="http" />
            <el-option label="Shell" value="shell" />
            <el-option label="Job" value="job" />
            <el-option label="MQ" value="mq" />
          </el-select>
        </el-form-item>
        <el-form-item :label="t('scheduler.status')">
          <el-select v-model="searchForm.status" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.statusEnabled')" value="enabled" />
            <el-option :label="t('scheduler.statusDisabled')" value="disabled" />
            <el-option :label="t('scheduler.statusPaused')" value="paused" />
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
          <el-button v-permission="['tasks.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>{{ t('common.create') }}
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" :label="t('scheduler.taskName')" min-width="150" show-overflow-tooltip />
        <el-table-column prop="project.name" :label="t('scheduler.belongsToProject')" min-width="120" />
        <el-table-column prop="cron_expression" :label="t('scheduler.cronExpression')" min-width="120" />
        <el-table-column :label="t('scheduler.executor')" width="100" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ row.executor_type.toUpperCase() }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.status')" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.lastRun')" min-width="170">
          <template #default="{ row }">
            <span v-if="row.last_run_at_local">{{ row.last_run_at_local }}</span>
            <span v-else class="text-muted">{{ t('scheduler.neverRun') }}</span>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.nextRun')" min-width="170">
          <template #default="{ row }">
            <span v-if="row.next_run_at_local">{{ row.next_run_at_local }}</span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="280" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">{{ t('common.edit') }}</el-button>
            <el-button type="success" link size="small" @click="handleTrigger(row)">{{ t('scheduler.trigger') }}</el-button>
            <el-button v-if="row.status === 'enabled'" type="warning" link size="small" @click="handlePause(row)">{{ t('scheduler.pause') }}</el-button>
            <el-button v-else-if="row.status === 'paused'" type="success" link size="small" @click="handleResume(row)">{{ t('scheduler.resume') }}</el-button>
            <el-button type="info" link size="small" @click="handleLogs(row)">{{ t('scheduler.logs') }}</el-button>
            <el-button v-permission="['tasks.destroy']" type="danger" link size="small" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination :pagination="pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <TaskFormDialog v-model:visible="dialogVisible" :edit-data="currentRow" :projects="projects" @submit="loadData" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, Plus } from '@element-plus/icons-vue'
import type { Task } from '@/api/task'
import { getTasks, deleteTask, triggerTask, pauseTask, resumeTask } from '@/api/task'
import { getProjects } from '@/api/project'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import TaskFormDialog from './components/TaskFormDialog.vue'

const { t } = useI18n()
const projects = ref<any[]>([])

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
} = useTable<Task>({
  fetchApi: getTasks,
  defaultSearch: {
    project_id: '',
    keyword: '',
    executor_type: '',
    status: '' as string,
  },
  immediate: false,
})

const dialogVisible = ref(false)
const currentRow = ref<Task | null>(null)

onMounted(async () => {
  try {
    const res = await getProjects({ per_page: 100 })
    projects.value = res.data || []
  } catch {}
  loadData()
})

function getStatusType(status: string) {
  const map: Record<string, any> = { enabled: 'success', disabled: 'info', paused: 'warning' }
  return map[status] || 'info'
}

function getStatusLabel(status: string) {
  const map: Record<string, string> = {
    enabled: t('scheduler.statusEnabled'),
    disabled: t('scheduler.statusDisabled'),
    paused: t('scheduler.statusPaused'),
  }
  return map[status] || status
}

function handleCreate() {
  currentRow.value = null
  dialogVisible.value = true
}

function handleEdit(row: Task) {
  currentRow.value = row
  dialogVisible.value = true
}

async function handleTrigger(row: Task) {
  try {
    await ElMessageBox.confirm(t('scheduler.triggerConfirm'), t('common.tip'), { type: 'warning' })
    await triggerTask(row.hash_id)
    ElMessage.success(t('scheduler.triggerSuccess'))
    loadData()
  } catch {}
}

async function handlePause(row: Task) {
  try {
    await pauseTask(row.hash_id)
    ElMessage.success(t('scheduler.pauseSuccess'))
    loadData()
  } catch {}
}

async function handleResume(row: Task) {
  try {
    await resumeTask(row.hash_id)
    ElMessage.success(t('scheduler.resumeSuccess'))
    loadData()
  } catch {}
}

function handleLogs(row: Task) {
  // 跳转到日志页面
  window.location.href = `/scheduler/task-logs?task_id=${row.hash_id}`
}

async function handleDelete(row: Task) {
  try {
    await ElMessageBox.confirm(t('scheduler.deleteConfirm'), t('common.tip'), { type: 'warning' })
    await deleteTask(row.hash_id)
    ElMessage.success(t('scheduler.deleteSuccess'))
    loadData()
  } catch {}
}
</script>

<style scoped>
.task-container { padding: 0; }
.search-card { margin-bottom: 16px; }
.search-card :deep(.el-card__body) { padding-bottom: 0; }
.table-card :deep(.el-card__body) { padding-top: 16px; }
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.text-muted { color: var(--el-text-color-placeholder); }
</style>
