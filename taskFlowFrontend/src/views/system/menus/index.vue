<template>
  <div class="menu-container">
    <el-card shadow="never">
      <!-- Toolbar -->
      <div class="table-toolbar">
        <div class="toolbar-left">
          <el-button
            v-permission="['menus.store']"
            type="primary"
            @click="handleCreate"
          >
            <el-icon><Plus /></el-icon>
            {{ t('common.create') }}
          </el-button>
          <el-button
            v-permission="['menus.batch-status']"
            type="success"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(1)"
          >
            {{ t('common.batchEnable') }}
          </el-button>
          <el-button
            v-permission="['menus.batch-status']"
            type="warning"
            :disabled="selectedRows.length === 0"
            @click="handleBatchStatus(0)"
          >
            {{ t('common.batchDisable') }}
          </el-button>
          <el-button
            v-permission="['menus.destroy']"
            type="danger"
            :disabled="selectedRows.length === 0"
            @click="handleBatchDelete"
          >
            {{ t('common.batchDelete') }}
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-tooltip :content="t('common.refresh')" placement="top">
            <el-button :icon="Refresh" @click="loadData">
              {{ t('common.refresh') }}
            </el-button>
          </el-tooltip>
        </div>
      </div>

      <!-- Tree Table -->
      <el-table
        v-loading="loading"
        :data="tableData"
        row-key="hash_id"
        default-expand-all
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
        border
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column
          prop="name"
          :label="t('menu.name')"
          min-width="200"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            <span style="display: inline-flex; align-items: center; gap: 6px;">
              <el-icon v-if="row.icon" :size="16">
                <component :is="row.icon" />
              </el-icon>
              <span>{{ row.name }}</span>
            </span>
          </template>
        </el-table-column>
        <el-table-column
          prop="path"
          :label="t('menu.path')"
          min-width="160"
          show-overflow-tooltip
        />
        <el-table-column
          prop="component"
          :label="t('menu.component')"
          min-width="160"
          show-overflow-tooltip
        />
        <el-table-column
          prop="type"
          :label="t('menu.type')"
          width="100"
          align="center"
        >
          <template #default="{ row }">
            <el-tag
              :type="menuTypeTagType(row.type)"
              size="small"
            >
              {{ menuTypeLabel(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="status"
          :label="t('common.status')"
          width="90"
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
          width="70"
          align="center"
        />
        <el-table-column
          prop="is_link"
          :label="t('menu.isLink')"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="row.is_link === 1 ? 'warning' : 'info'" size="small">
              {{ row.is_link === 1 ? t('common.yes') : t('common.no') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="keep_alive"
          :label="t('menu.keepAlive')"
          width="90"
          align="center"
        >
          <template #default="{ row }">
            <el-tag :type="row.keep_alive === 1 ? 'success' : 'info'" size="small">
              {{ row.keep_alive === 1 ? t('common.yes') : t('common.no') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          :label="t('common.actions')"
          width="150"
          align="center"
          fixed="right"
        >
          <template #default="{ row }">
            <el-button
              v-permission="['menus.update']"
              type="primary"
              link
              size="small"
              @click="handleEdit(row)"
            >
              {{ t('common.edit') }}
            </el-button>
            <el-button
              v-permission="['menus.store']"
              type="success"
              link
              size="small"
              @click="handleAddChild(row)"
            >
              {{ t('common.create') }}
            </el-button>
            <el-button
              v-permission="['menus.destroy']"
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
    </el-card>

    <!-- Form Dialog -->
    <MenuFormDialog
      v-model:visible="dialogVisible"
      :edit-data="currentRow"
      :parent-id="currentParentId"
      @submit="loadData"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import { getMenus, deleteMenu, batchUpdateMenuStatus } from '@/api/menu'
import type { Menu } from '@/types/api'
import MenuFormDialog from './components/MenuFormDialog.vue'

const { t } = useI18n()

const loading = ref(false)
const tableData = ref<Menu[]>([])
const selectedRows = ref<Menu[]>([])

// Dialog state
const dialogVisible = ref(false)
const currentRow = ref<Menu | null>(null)
const currentParentId = ref<string | null>(null)

async function loadData() {
  loading.value = true
  try {
    const res = await getMenus()
    tableData.value = Array.isArray(res) ? res : []
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.loadDataFailed'))
    tableData.value = []
  } finally {
    loading.value = false
  }
}

function handleSelectionChange(rows: Menu[]) {
  selectedRows.value = rows
}

function handleCreate() {
  currentRow.value = null
  currentParentId.value = null
  dialogVisible.value = true
}

function handleEdit(row: Menu) {
  currentRow.value = row
  currentParentId.value = null
  dialogVisible.value = true
}

function handleAddChild(row: Menu) {
  currentRow.value = null
  currentParentId.value = row.hash_id
  dialogVisible.value = true
}

async function handleDelete(row: Menu) {
  // Check for children on the client side
  if (row.children && row.children.length > 0) {
    ElMessage.warning(t('menu.hasChildren'))
    return
  }

  try {
    await ElMessageBox.confirm(t('common.deleteConfirm'), t('common.tip'), {
      type: 'warning',
    })
    await deleteMenu(row.hash_id)
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch (error: any) {
    // If it's an error from the API (not cancellation), show message
    if (error !== 'cancel' && error !== undefined) {
      const msg = error?.message || ''
      if (msg.includes('children') || msg.includes('子菜单')) {
        ElMessage.warning(t('menu.hasChildren'))
      }
    }
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
    await batchUpdateMenuStatus({ ids, status })
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

  // Filter out rows that have children
  const deletableRows = selectedRows.value.filter(
    (row) => !row.children || row.children.length === 0,
  )
  const hasChildrenCount = selectedRows.value.length - deletableRows.length

  if (deletableRows.length === 0) {
    ElMessage.warning(t('menu.hasChildren'))
    return
  }

  if (hasChildrenCount > 0) {
    ElMessage.warning(t('menu.hasChildren'))
  }

  try {
    await ElMessageBox.confirm(
      t('common.batchDeleteConfirm', { count: deletableRows.length }),
      t('common.tip'),
      { type: 'warning' },
    )
  } catch {
    return
  }

  try {
    for (const row of deletableRows) {
      await deleteMenu(row.hash_id)
    }
    ElMessage.success(t('common.deleteSuccess'))
    loadData()
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  }
}

function menuTypeLabel(type: number): string {
  const map: Record<number, string> = {
    1: t('menu.typeDirectory'),
    2: t('menu.typeMenu'),
    3: t('menu.typeButton'),
  }
  return map[type] || '-'
}

function menuTypeTagType(type: number): string {
  const map: Record<number, string> = {
    1: '',
    2: 'success',
    3: 'warning',
  }
  return map[type] || 'info'
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.menu-container {
  padding: 0;
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
