<template>
  <div class="permission-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('permission.name')">
          <el-input
            v-model="searchForm.name"
            :placeholder="t('permission.namePlaceholder')"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('permission.slug')">
          <el-input
            v-model="searchForm.slug"
            :placeholder="t('permission.slugPlaceholder')"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">
            <el-icon><Search /></el-icon>
            {{ t('common.search') }}
          </el-button>
          <el-button @click="handleReset">
            <el-icon><Refresh /></el-icon>
            {{ t('common.reset') }}
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- Table Card -->
    <el-card shadow="never" class="table-card">
      <!-- Toolbar -->
      <div class="table-toolbar">
        <div class="toolbar-left">
          <el-button
            v-permission="['permissions.store']"
            type="primary"
            @click="handleCreate"
          >
            <el-icon><Plus /></el-icon>
            {{ t('common.create') }}
          </el-button>
          <el-button
            v-permission="['permissions.sync-routes']"
            type="success"
            @click="handleSyncRoutes"
          >
            <el-icon><RefreshRight /></el-icon>
            {{ t('permission.syncRoutes') }}
          </el-button>
          <el-button
            v-permission="['permissions.batch-status']"
            type="success"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            {{ t('common.batchEnable') }}
          </el-button>
          <el-button
            v-permission="['permissions.batch-status']"
            type="warning"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            {{ t('common.batchDisable') }}
          </el-button>
          <el-button
            v-permission="['permissions.destroy']"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            {{ t('common.batchDelete') }}
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">
              {{ t('common.refresh') }}
            </el-button>
        </div>
      </div>

      <!-- Table -->
      <el-table
        v-loading="loading"
        :data="tableData"
        row-key="hash_id"
        border
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column
          prop="name"
          :label="t('permission.name')"
          min-width="150"
          show-overflow-tooltip
        />
        <el-table-column
          prop="slug"
          :label="t('permission.slug')"
          min-width="180"
          show-overflow-tooltip
        />
        <el-table-column
          prop="http_method"
          :label="t('permission.httpMethod')"
          width="120"
          align="center"
        >
          <template #default="{ row }">
            <el-tag
              v-if="row.http_method"
              :type="httpMethodTagType(row.http_method)"
              size="small"
            >
              {{ row.http_method }}
            </el-tag>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column
          prop="http_path"
          :label="t('permission.httpPath')"
          min-width="200"
          show-overflow-tooltip
        />
        <el-table-column
          prop="status"
          :label="t('common.status')"
          width="100"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? t('common.enabled') : t('common.disabled') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="sort"
          :label="t('common.sort')"
          width="80"
          align="center"
        />
        <el-table-column
          :label="t('common.actions')"
          width="160"
          align="center"
          fixed="right"
        >
          <template #default="{ row }">
            <el-button
              v-permission="['permissions.update']"
              type="primary"
              link
              size="small"
              @click="handleEdit(row)"
            >
              {{ t('common.edit') }}
            </el-button>
            <el-button
              v-permission="['permissions.destroy']"
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

      <!-- Pagination -->
      <Pagination
        :pagination="pagination"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </el-card>

    <!-- Form Dialog -->
    <PermissionFormDialog
      v-model:visible="dialogVisible"
      :edit-data="currentRow"
      @submit="loadData"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { useTable } from '@/composables/useTable'
import {
  getPermissions,
  deletePermission,
  batchUpdatePermissionStatus,
  syncRoutes,
} from '@/api/permission'
import type { Permission } from '@/types/api'
import Pagination from '@/components/Pagination/index.vue'
import PermissionFormDialog from './components/PermissionFormDialog.vue'

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
} = useTable<Permission>({
  fetchApi: getPermissions,
  defaultSearch: { name: '', slug: '' },
})

// Dialog state
const dialogVisible = ref(false)
const currentRow = ref<Permission | null>(null)

function handleCreate() {
  currentRow.value = null
  dialogVisible.value = true
}

function handleEdit(row: Permission) {
  currentRow.value = row
  dialogVisible.value = true
}

async function handleDelete(row: Permission) {
  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), {
      type: 'warning',
    })
    await deletePermission(row.hash_id)
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
  }
}

async function handleSyncRoutes() {
  try {
    await ElMessageBox.confirm(
      t('permission.syncRoutesConfirm'),
      t('common.tip'),
      { type: 'warning' }
    )
    await syncRoutes()
    ElMessage.success(t('permission.syncRoutesSuccess'))
    loadData()
  } catch {
    // cancelled or error
  }
}

async function handleBatchStatus(status: number) {
  if (selectedRows.value.length === 0) {
    ElMessage.warning(t('common.selectAtLeast'))
    return
  }
  const count = selectedRows.value.length
  const confirmMsg = status === 1
    ? t('common.batchEnableConfirm', { count })
    : t('common.batchDisableConfirm', { count })
  try {
    await ElMessageBox.confirm(confirmMsg, t('common.tip'), { type: 'warning' })
  } catch {
    return
  }
  try {
    const ids = selectedRows.value.map((row) => row.hash_id)
    await batchUpdatePermissionStatus({ ids, status })
    ElMessage.success(t('common.operationSuccess'))
    loadData()
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
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
  } catch {
    return
  }
  try {
    await Promise.all(selectedRows.value.map((row) => deletePermission(row.hash_id)))
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  }
}

function httpMethodTagType(method: string): string {
  const map: Record<string, string> = {
    GET: 'success',
    POST: 'warning',
    PUT: 'info',
    DELETE: 'danger',
    PATCH: '',
    OPTIONS: '',
    HEAD: '',
  }
  return map[method.toUpperCase()] || ''
}
</script>

<style scoped>
.permission-container {
  padding: 0;
}
.search-card {
  margin-bottom: 16px;
}
.search-card :deep(.el-card__body) {
  padding-bottom: 0;
}
.table-card :deep(.el-card__body) {
  padding-top: 16px;
}
.table-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.toolbar-left {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
</style>
