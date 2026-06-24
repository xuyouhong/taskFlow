<template>
  <div class="node-container">
    <!-- Search Form -->
    <el-card shadow="never" class="search-card">
      <el-form :model="searchForm" inline @submit.prevent="handleSearch">
        <el-form-item label="节点名称">
          <el-input v-model="searchForm.keyword" placeholder="节点名称/IP" clearable @clear="handleSearch" />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable style="width: 120px" @clear="handleSearch">
            <el-option label="在线" value="online" />
            <el-option label="离线" value="offline" />
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
          <el-button v-permission="['nodes.store']" type="primary" @click="handleCreate">
            <el-icon><Plus /></el-icon>添加节点
          </el-button>
        </div>
        <div class="toolbar-right">
          <el-button :icon="Refresh" @click="loadData">{{ t('common.refresh') }}</el-button>
        </div>
      </div>

      <el-table v-loading="loading" :data="tableData" row-key="hash_id" border stripe>
        <el-table-column prop="name" label="节点名称" min-width="120" show-overflow-tooltip />
        <el-table-column prop="ip" label="IP地址" min-width="140" />
        <el-table-column prop="hostname" label="主机名" min-width="120" show-overflow-tooltip />
        <el-table-column label="Agent端口" width="100" align="center">
          <template #default="{ row }">{{ row.agent_port }}</template>
        </el-table-column>
        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status === 'online' ? 'success' : 'danger'" size="small">
              {{ row.status === 'online' ? '在线' : '离线' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="最后心跳" min-width="170">
          <template #default="{ row }">{{ formatDateTime(row.last_heartbeat_at) }}</template>
        </el-table-column>
        <el-table-column label="配置信息" min-width="150">
          <template #default="{ row }">
            <span v-if="row.cpu_cores">CPU: {{ row.cpu_cores }}核</span>
            <span v-if="row.memory_total_mb"> / 内存: {{ row.memory_total_mb }}MB</span>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="160" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button v-permission="['nodes.destroy']" type="danger" link size="small" @click="handleDelete(row)">删除</el-button>
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
    await ElMessageBox.confirm('确定要删除该节点吗？', '提示', { type: 'warning' })
    await deleteNode(row.hash_id)
    ElMessage.success('删除成功')
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
