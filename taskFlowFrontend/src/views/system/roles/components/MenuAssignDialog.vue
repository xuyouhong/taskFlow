<template>
  <el-dialog
    :model-value="visible"
    :title="`${t('role.assignMenus')} - ${roleData?.name || ''}`"
    width="550px"
    draggable
    :close-on-click-modal="false"
    :destroy-on-close="true"
    @update:model-value="$emit('update:visible', $event)"
    @closed="handleClosed"
  >
    <div v-loading="dialogLoading" class="menu-tree-container">
      <!-- Toolbar -->
      <div class="tree-toolbar">
        <el-button size="small" @click="handleSelectAll">
          {{ allSelected ? t('role.unselectAll') : t('role.selectAll') }}
        </el-button>
        <el-button size="small" @click="handleExpandAll">
          {{ expandAll ? t('role.collapseAll') : t('role.expandAll') }}
        </el-button>
      </div>

      <!-- Tree -->
      <div class="tree-wrapper">
        <el-tree
          ref="treeRef"
          :data="menuTree"
          :props="treeProps"
          node-key="hash_id"
          show-checkbox
          default-expand-all
          :check-strictly="false"
        />
      </div>
    </div>

    <template #footer>
      <el-button @click="$emit('update:visible', false)">
        {{ t('common.cancel') }}
      </el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
        {{ t('common.confirm') }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElTree, ElMessage } from 'element-plus'
import type { Menu } from '@/types/api'
import { getMenusList, getRoleMenus, assignMenusToRole } from '@/api/role'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  roleData: { hash_id: string; name: string } | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  submit: []
}>()

const treeRef = ref<InstanceType<typeof ElTree>>()
const dialogLoading = ref(false)
const submitLoading = ref(false)
const menuTree = ref<Menu[]>([])
const allSelected = ref(false)
const expandAll = ref(true)

const treeProps = {
  label: 'name',
  children: 'children',
}

// Collect all leaf node keys from tree data
function getLeafKeys(nodes: Menu[]): Set<string> {
  const leafKeys = new Set<string>()
  function walk(items: Menu[]) {
    for (const item of items) {
      if (item.children && item.children.length > 0) {
        walk(item.children)
      } else {
        leafKeys.add(item.hash_id)
      }
    }
  }
  walk(nodes)
  return leafKeys
}

// Collect all node keys from tree data
function getAllKeys(nodes: Menu[]): string[] {
  const keys: string[] = []
  function walk(items: Menu[]) {
    for (const item of items) {
      keys.push(item.hash_id)
      if (item.children && item.children.length > 0) {
        walk(item.children)
      }
    }
  }
  walk(nodes)
  return keys
}

watch(
  () => props.visible,
  async (val) => {
    if (val && props.roleData) {
      dialogLoading.value = true
      try {
        const [treeData, roleMenus] = await Promise.all([
          getMenusList(),
          getRoleMenus(props.roleData.hash_id),
        ])

        menuTree.value = treeData as Menu[]

        // Extract assigned menu hash_ids
        const assignedIds = (roleMenus as any[]).map((item) =>
          typeof item === 'string' ? item : item.hash_id
        )

        // Only set leaf keys to avoid auto-checking all children via parent
        await nextTick()
        const leafKeys = getLeafKeys(menuTree.value)
        const checkedLeafKeys = assignedIds.filter((id) => leafKeys.has(id))
        treeRef.value?.setCheckedKeys(checkedLeafKeys)
        allSelected.value = false
        expandAll.value = true
      } catch (error: any) {
        ElMessage.error(error?.message || t('common.operationFailed'))
      } finally {
        dialogLoading.value = false
      }
    }
  }
)

function handleClosed() {
  menuTree.value = []
  allSelected.value = false
}

function handleSelectAll() {
  if (allSelected.value) {
    treeRef.value?.setCheckedKeys([])
    allSelected.value = false
  } else {
    const allKeys = getAllKeys(menuTree.value)
    treeRef.value?.setCheckedKeys(allKeys)
    allSelected.value = true
  }
}

function handleExpandAll() {
  expandAll.value = !expandAll.value
  const nodes = (treeRef.value as any)?.store?.nodesMap
  if (nodes) {
    Object.values(nodes).forEach((node: any) => {
      node.expanded = expandAll.value
    })
  }
}

async function handleSubmit() {
  if (!props.roleData || !treeRef.value) return

  submitLoading.value = true
  try {
    // Get both fully checked and half-checked (parent) keys
    const checkedKeys = treeRef.value.getCheckedKeys(false) as string[]
    const halfCheckedKeys = treeRef.value.getHalfCheckedKeys() as string[]
    const menuIds = [...checkedKeys, ...halfCheckedKeys]

    await assignMenusToRole(props.roleData.hash_id, { menu_ids: menuIds })
    ElMessage.success(t('role.menuAssignSuccess'))
    emit('update:visible', false)
    emit('submit')
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  } finally {
    submitLoading.value = false
  }
}
</script>

<style scoped>
.menu-tree-container {
  min-height: 200px;
}
.tree-toolbar {
  margin-bottom: 12px;
  display: flex;
  gap: 8px;
}
.tree-wrapper {
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  padding: 8px;
}
</style>
