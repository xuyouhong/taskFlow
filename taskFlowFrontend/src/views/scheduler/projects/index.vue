<template>
  <div class="project-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('scheduler.projectName')">
          <el-input v-model="searchForm.keyword" :placeholder="t('scheduler.projectNamePlaceholder')" clearable @clear="handleSearch" />
        </el-form-item>
        <el-form-item :label="t('scheduler.projectStatus')">
          <el-select v-model="searchForm.status" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.projectEnabled')" :value="1" />
            <el-option :label="t('scheduler.projectDisabled')" :value="0" />
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
          <el-button v-permission="['projects.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>{{ t('scheduler.createProject') }}
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" :label="t('scheduler.projectName')" min-width="150" show-overflow-tooltip />
        <el-table-column prop="code" :label="t('scheduler.projectCode')" min-width="120" />
        <el-table-column prop="description" :label="t('scheduler.projectDescription')" min-width="200" show-overflow-tooltip />
        <el-table-column prop="owner.username" :label="t('scheduler.projectOwner')" min-width="100" />
        <el-table-column :label="t('scheduler.projectStatus')" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? t('scheduler.projectEnabled') : t('scheduler.projectDisabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.projectCreatedAt')" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.created_at) }}</template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="200" align="center" fixed="right">
          <template #default="{ row }">
            <el-button v-permission="['projects.update']" type="primary" link size="small" @click="handleEdit(row)">{{ t('common.edit') }}</el-button>
            <el-button v-permission="['projects.destroy']" type="danger" link size="small" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination :pagination="pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <ProjectFormDialog v-model:visible="dialogVisible" :edit-data="currentRow" @submit="loadData" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, Plus } from '@element-plus/icons-vue'
import type { Project } from '@/api/project'
import { getProjects, deleteProject } from '@/api/project'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import ProjectFormDialog from './components/ProjectFormDialog.vue'
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
} = useTable<Project>({
  fetchApi: getProjects,
  defaultSearch: {
    keyword: '',
    status: '' as string | number,
  },
})

const dialogVisible = ref(false)
const currentRow = ref<Project | null>(null)

function handleCreate() {
  currentRow.value = null
  dialogVisible.value = true
}

function handleEdit(row: Project) {
  currentRow.value = row
  dialogVisible.value = true
}

async function handleDelete(row: Project) {
  try {
    await ElMessageBox.confirm(t('scheduler.projectDeleteConfirm'), t('common.tip'), { type: 'warning' })
    await deleteProject(row.hash_id)
    ElMessage.success(t('scheduler.deleteSuccess'))
    loadData()
  } catch {}
}
</script>

<style scoped>
.project-container { padding: 0; }
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
