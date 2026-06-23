<template>
  <div class="user-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('user.username')">
          <el-input
            v-model="searchForm.username"
            :placeholder="t('user.usernamePlaceholder')"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('user.realName')">
          <el-input
            v-model="searchForm.real_name"
            :placeholder="t('user.realNamePlaceholder')"
            clearable
            @clear="handleSearch"
          />
        </el-form-item>
        <el-form-item :label="t('common.status')">
          <el-select
            v-model="searchForm.status"
            :placeholder="t('user.statusPlaceholder')"
            clearable
            style="width: 120px"
            @clear="handleSearch"
          >
            <el-option :label="t('common.enabled')" :value="1" />
            <el-option :label="t('common.disabled')" :value="0" />
          </el-select>
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
            v-permission="['users.store']"
            type="primary"
            @click="handleCreate"
          >
            <el-icon><Plus /></el-icon>
            {{ t('common.create') }}
          </el-button>
          <el-button
            v-permission="['users.batch-status']"
            type="warning"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            {{ t('common.batchEnable') }}
          </el-button>
          <el-button
            v-permission="['users.batch-status']"
            type="success"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            {{ t('common.batchDisable') }}
          </el-button>
          <el-button
            v-permission="['users.destroy']"
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
          prop="username"
          :label="t('user.username')"
          min-width="120"
          show-overflow-tooltip
        />
        <el-table-column
          prop="real_name"
          :label="t('user.realName')"
          min-width="120"
          show-overflow-tooltip
        />
        <el-table-column
          prop="email"
          :label="t('user.email')"
          min-width="180"
          show-overflow-tooltip
        />
        <el-table-column
          prop="phone"
          :label="t('user.phone')"
          min-width="130"
          show-overflow-tooltip
        />
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
        <el-table-column :label="t('user.roles')" min-width="200">
          <template #default="{ row }">
            <el-tag
              v-for="role in row.roles"
              :key="role.hash_id"
              class="role-tag"
              size="small"
            >
              {{ role.name }}
            </el-tag>
            <span v-if="!row.roles || row.roles.length === 0" class="text-muted">-</span>
          </template>
        </el-table-column>
        <el-table-column
          prop="last_login_at"
          :label="t('user.lastLoginAt')"
          min-width="170"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            {{ row.last_login_at || '-' }}
          </template>
        </el-table-column>
        <el-table-column
          :label="t('common.actions')"
          width="160"
          align="center"
          fixed="right"
        >
          <template #default="{ row }">
            <el-button
              v-permission="['users.update']"
              type="primary"
              link
              size="small"
              @click="handleEdit(row)"
            >
              {{ t('common.edit') }}
            </el-button>
            <el-button
              v-permission="['users.destroy']"
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
    <UserFormDialog
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
import type { User } from '@/types/api'
import { getUsers, deleteUser, batchUpdateUserStatus } from '@/api/user'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import UserFormDialog from './components/UserFormDialog.vue'

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
} = useTable<User>({
  fetchApi: getUsers,
  defaultSearch: {
    username: '',
    real_name: '',
    status: '' as string | number,
  },
})

// Dialog state
const dialogVisible = ref(false)
const currentRow = ref<User | null>(null)

function handleCreate() {
  currentRow.value = null
  dialogVisible.value = true
}

function handleEdit(row: User) {
  currentRow.value = row
  dialogVisible.value = true
}

async function handleDelete(row: User) {
  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), {
      type: 'warning',
    })
    await deleteUser(row.hash_id)
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
    await Promise.all(selectedRows.value.map((row) => deleteUser(row.hash_id)))
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
    await batchUpdateUserStatus({
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
.user-container {
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
.role-tag {
  margin-right: 4px;
  margin-bottom: 2px;
}
.text-muted {
  color: var(--el-text-color-placeholder);
}
</style>
