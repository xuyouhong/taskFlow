<template>
  <div class="task-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item label="项目">
          <el-select v-model="searchForm.project_id" placeholder="请选择项目" clearable style="width: 150px" @clear="handleSearch">
            <el-option v-for="p in projects" :key="p.hash_id" :label="p.name" :value="p.hash_id" />
          </el-select>
        </el-form-item>
        <el-form-item label="任务名称">
          <el-input v-model="searchForm.keyword" placeholder="任务名称" clearable @clear="handleSearch" />
        </el-form-item>
        <el-form-item label="执行器">
          <el-select v-model="searchForm.executor_type" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="HTTP" value="http" />
            <el-option label="Shell" value="shell" />
            <el-option label="Job" value="job" />
            <el-option label="MQ" value="mq" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="启用" value="enabled" />
            <el-option label="禁用" value="disabled" />
            <el-option label="暂停" value="paused" />
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
          <el-button v-permission="['tasks.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>创建
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" label="任务名称" min-width="150" show-overflow-tooltip />
        <el-table-column prop="project.name" label="所属项目" min-width="120" />
        <el-table-column prop="cron_expression" label="Cron表达式" min-width="120" />
        <el-table-column label="执行器" width="100" align="center">
          <template #default="{ row }">
            <el-tag size="small">{{ row.executor_type.toUpperCase() }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)" size="small">{{ getStatusLabel(row.status) }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="上次执行" min-width="170">
          <template #default="{ row }">
            <span v-if="row.last_run_at_local">{{ row.last_run_at_local }}</span>
            <span v-else class="text-muted">从未执行</span>
          </template>
        </el-table-column>
        <el-table-column label="下次执行" min-width="170">
          <template #default="{ row }">
            <span v-if="row.next_run_at_local">{{ row.next_run_at_local }}</span>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="280" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" link size="small" @click="handleTrigger(row)">执行</el-button>
            <el-button v-if="row.status === 'enabled'" type="warning" link size="small" @click="handlePause(row)">暂停</el-button>
            <el-button v-else-if="row.status === 'paused'" type="success" link size="small" @click="handleResume(row)">恢复</el-button>
            <el-button type="info" link size="small" @click="handleLogs(row)">日志</el-button>
            <el-button v-permission="['tasks.destroy']" type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
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
  const map: Record<string, string> = { enabled: '启用', disabled: '禁用', paused: '暂停' }
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
    await ElMessageBox.confirm('确定要立即执行该任务吗？', '提示', { type: 'warning' })
    await triggerTask(row.hash_id)
    ElMessage.success('触发成功')
    loadData()
  } catch {}
}

async function handlePause(row: Task) {
  try {
    await pauseTask(row.hash_id)
    ElMessage.success('已暂停')
    loadData()
  } catch {}
}

async function handleResume(row: Task) {
  try {
    await resumeTask(row.hash_id)
    ElMessage.success('已恢复')
    loadData()
  } catch {}
}

function handleLogs(row: Task) {
  // 跳转到日志页面
  window.location.href = `/scheduler/task-logs?task_id=${row.hash_id}`
}

async function handleDelete(row: Task) {
  try {
    await ElMessageBox.confirm('确定要删除该任务吗？', '提示', { type: 'warning' })
    await deleteTask(row.hash_id)
    ElMessage.success('删除成功')
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
