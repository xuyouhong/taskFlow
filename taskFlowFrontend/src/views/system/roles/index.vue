<template>
  <div class="role-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('role.name')">
          <el-input
            v-model="searchForm.name"
            :placeholder="t('role.namePlaceholder')"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('role.slug')">
          <el-input
            v-model="searchForm.slug"
            :placeholder="t('role.slugPlaceholder')"
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
            v-permission="['roles.store']"
            type="primary"
            @click="handleCreate"
          >
            <el-icon><Plus /></el-icon>
            {{ t('common.create') }}
          </el-button>
          <el-button
            v-permission="['roles.batch-status']"
            type="success"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            {{ t('common.batchEnable') }}
          </el-button>
          <el-button
            v-permission="['roles.batch-status']"
            type="warning"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            {{ t('common.batchDisable') }}
          </el-button>
          <el-button
            v-permission="['roles.destroy']"
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
          :label="t('role.name')"
          min-width="130"
          show-overflow-tooltip
        />
        <el-table-column
          prop="slug"
          :label="t('role.slug')"
          min-width="130"
          show-overflow-tooltip
        />
        <el-table-column
          prop="description"
          :label="t('common.description')"
          min-width="200"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.description || '-' }}
          </template>
        </el-table-column>
        <el-table-column
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
          width="250"
          align="center"
          fixed="right"
        >
          <template #default="{ row }">
            <el-button
              v-permission="['roles.update']"
              type="primary"
              link
              size="small"
              @click="handleEdit(row)"
            >
              {{ t('common.edit') }}
            </el-button>
            <el-button
              v-permission="['roles.assign-menus']"
              type="success"
              link
              size="small"
              @click="handleAssignMenus(row)"
            >
              {{ t('role.assignMenus') }}
            </el-button>
            <el-button
              v-permission="['roles.assign-permissions']"
              type="warning"
              link
              size="small"
              @click="handleAssignPermissions(row)"
            >
              {{ t('role.assignPermissions') }}
            </el-button>
            <el-button
              v-permission="['roles.destroy']"
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

    <!-- Create/Edit Dialog -->
    <RoleFormDialog
      v-model:visible="formDialogVisible"
      :edit-data="currentRow"
      @submit="loadData"
    />

    <!-- Menu Assign Dialog -->
    <MenuAssignDialog
      v-model:visible="menuDialogVisible"
      :role-data="currentRole"
      @submit="loadData"
    />

    <!-- Permission Assign Dialog -->
    <PermissionAssignDialog
      v-model:visible="permissionDialogVisible"
      :role-data="currentRole"
      @submit="loadData"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import type { Role } from '@/types/api'
import { getRoles, deleteRole, batchUpdateRoleStatus } from '@/api/role'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import RoleFormDialog from './components/RoleFormDialog.vue'
import MenuAssignDialog from './components/MenuAssignDialog.vue'
import PermissionAssignDialog from './components/PermissionAssignDialog.vue'

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
} = useTable<Role>({
  fetchApi: getRoles,
  defaultSearch: { name: '', slug: '' },
})

// Dialog state
const formDialogVisible = ref(false)
const menuDialogVisible = ref(false)
const permissionDialogVisible = ref(false)
const currentRow = ref<Role | null>(null)
const currentRole = ref<{ hash_id: string; name: string } | null>(null)

function handleCreate() {
  currentRow.value = null
  formDialogVisible.value = true
}

function handleEdit(row: Role) {
  currentRow.value = row
  formDialogVisible.value = true
}

function handleAssignMenus(row: Role) {
  currentRole.value = { hash_id: row.hash_id, name: row.name }
  menuDialogVisible.value = true
}

function handleAssignPermissions(row: Role) {
  currentRole.value = { hash_id: row.hash_id, name: row.name }
  permissionDialogVisible.value = true
}

async function handleDelete(row: Role) {
  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), {
      type: 'warning',
    })
    await deleteRole(row.hash_id)
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
    await Promise.all(selectedRows.value.map((row) => deleteRole(row.hash_id)))
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch {
    // cancelled
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
    await batchUpdateRoleStatus({
      ids: selectedRows.value.map((row) => row.hash_id),
      status,
    })
    ElMessage.success(t('common.operationSuccess'))
    loadData()
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  }
}
</script>

<style scoped>
.role-container {
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
