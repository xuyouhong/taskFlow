<template>
  <div class="node-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item :label="t('scheduler.nodeName')">
          <el-input v-model="searchForm.keyword" :placeholder="t('scheduler.nodeNamePlaceholder')" clearable @clear="handleSearch" />
        </el-form-item>
        <el-form-item :label="t('scheduler.nodeStatus')">
          <el-select v-model="searchForm.status" :placeholder="t('common.all')" clearable style="width: 120px" @clear="handleSearch">
            <el-option :label="t('scheduler.nodeOnline')" value="online" />
            <el-option :label="t('scheduler.nodeOffline')" value="offline" />
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
          <el-button v-permission="['nodes.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>{{ t('scheduler.createNode') }}
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" :label="t('scheduler.nodeName')" min-width="120" show-overflow-tooltip />
        <el-table-column prop="ip" :label="t('scheduler.nodeIp')" min-width="140" />
        <el-table-column prop="hostname" :label="t('scheduler.nodeHostname')" min-width="120" show-overflow-tooltip />
        <el-table-column :label="t('scheduler.nodeAgentPort')" width="100" align="center">
          <template #default="{ row }">{{ row.agent_port }}</template>
        </el-table-column>
        <el-table-column :label="t('scheduler.nodeStatus')" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 'online' ? 'success' : 'danger'" size="small">
              {{ row.status === 'online' ? t('scheduler.nodeOnline') : t('scheduler.nodeOffline') }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column :label="t('scheduler.nodeLastHeartbeat')" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.last_heartbeat_at) }}</template>
        </el-table-column>
        <el-table-column :label="t('scheduler.nodeConfigInfo')" min-width="150">
          <template #default="{ row }">
            <span v-if="row.cpu_cores">{{ t('scheduler.nodeCpuCores', { cores: row.cpu_cores }) }}</span>
            <span v-if="row.memory_total_mb"> / {{ t('scheduler.nodeMemoryTotal', { mb: row.memory_total_mb }) }}</span>
          </template>
        </el-table-column>
        <el-table-column :label="t('common.actions')" width="160" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">{{ t('common.edit') }}</el-button>
            <el-button v-permission="['nodes.destroy']" type="danger" link size="small" @click="handleDelete(row)">{{ t('common.delete') }}</el-button>
          </template>
        </el-table-column>
      </el-table>

      <Pagination :pagination="pagination" @size-change="handleSizeChange" @current-change="handleCurrentChange" />
    </el-card>

    <NodeFormDialog v-model:visible="dialogVisible" :edit-data="currentRow" @submit="loadData" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Refresh, Plus } from '@element-plus/icons-vue'
import type { Node } from '@/api/node'
import { getNodes, deleteNode } from '@/api/node'
import { useTable } from '@/composables/useTable'
import Pagination from '@/components/Pagination/index.vue'
import NodeFormDialog from './components/NodeFormDialog.vue'
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
} = useTable<Node>({
  fetchApi: getNodes,
  defaultSearch: {
    keyword: '',
    status: '' as string,
  },
})

const dialogVisible = ref(false)
const currentRow = ref<Node | null>(null)

function handleCreate() {
  currentRow.value = null
  dialogVisible.value = true
}

function handleEdit(row: Node) {
  currentRow.value = row
  dialogVisible.value = true
}

async function handleDelete(row: Node) {
  try {
    await ElMessageBox.confirm(t('scheduler.nodeDeleteConfirm'), t('common.tip'), { type: 'warning' })
    await deleteNode(row.hash_id)
    ElMessage.success(t('scheduler.deleteSuccess'))
    loadData()
  } catch {}
}
</script>

<style scoped>
.node-container { padding: 0; }
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
