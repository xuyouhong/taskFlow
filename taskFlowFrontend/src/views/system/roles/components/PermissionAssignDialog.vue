<template>
  <el-dialog
    :model-value="visible"
    :title="`${t('role.assignPermissions')} - ${roleData?.name || ''}`"
    width="800px"
    draggable
    :close-on-click-modal="false"
    :destroy-on-close="true"
    @update:model-value="$emit('update:visible', $event)"
    @closed="handleClosed"
  >
    <div v-loading="dialogLoading" class="permission-container">
      <!-- Toolbar -->
      <div class="permission-toolbar">
        <el-input
          v-model="filterText"
          :placeholder="t('common.search')"
          clearable
          size="default"
          style="width: 300px"
        />
        <div class="toolbar-actions">
          <el-button size="small" @click="checkAll">{{ t('role.selectAll') }}</el-button>
          <el-button size="small" @click="uncheckAll">{{ t('role.unselectAll') }}</el-button>
        </div>
      </div>

      <!-- Permission Modules -->
      <div class="permission-list">
        <div
          v-for="module in filteredModules"
          :key="module.id"
          class="permission-module"
        >
          <!-- Module Header -->
          <div class="module-header">
            <el-checkbox
              :model-value="isModuleAllSelected(module)"
              :indeterminate="isModuleIndeterminate(module)"
              @change="(val: boolean | string | number) => handleModuleCheck(module, val)"
            >
              <span class="module-name">{{ module.name }}</span>
              <el-tag size="small" type="info" effect="plain" class="module-count">
                {{ module.children.length }}
              </el-tag>
            </el-checkbox>
          </div>

          <!-- Module Permissions -->
          <div class="module-items">
            <div
              v-for="perm in module.children"
              :key="perm.hash_id"
              class="permission-item"
            >
              <el-checkbox
                :model-value="selectedMap[perm.hash_id] || false"
                @change="(val: boolean | string | number) => handlePermCheck(perm.hash_id, val)"
              >
                <span class="perm-name">{{ perm.name }}</span>
                <el-tag size="small" type="info" effect="plain" class="perm-slug">{{ perm.slug }}</el-tag>
                <el-tag
                  v-if="perm.http_method"
                  size="small"
                  :type="methodTagType(perm.http_method)"
                  class="perm-method"
                >
                  {{ perm.http_method }}
                </el-tag>
                <span v-if="perm.http_path" class="perm-path">{{ perm.http_path }}</span>
              </el-checkbox>
            </div>
          </div>
        </div>

        <el-empty
          v-if="filteredModules.length === 0"
          :description="t('common.noData')"
          :image-size="80"
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
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import type { Permission } from '@/types/api'
import { getPermissionsList, getRolePermissions, assignPermissionsToRole } from '@/api/role'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  roleData: { hash_id: string; name: string } | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  submit: []
}>()

interface PermissionModule {
  id: string
  name: string
  children: Permission[]
}

const dialogLoading = ref(false)
const submitLoading = ref(false)
const allPermissions = ref<Permission[]>([])
const selectedMap = ref<Record<string, boolean>>({})
const filterText = ref('')

// Module name mapping from slug prefix to display name
const moduleNameMap: Record<string, string> = {
  users: t('layout.userManagement'),
  roles: t('layout.roleManagement'),
  permissions: t('layout.permissionManagement'),
  menus: t('layout.menuManagement'),
  'login-logs': t('layout.loginLog'),
  'operation-logs': t('layout.operationLog'),
  'notifications': t('layout.notificationManagement'),
  dashboard: t('layout.dashboard'),
  profile: t('layout.profile'),
  password: t('layout.password'),
}

function getModuleName(slugPrefix: string): string {
  return moduleNameMap[slugPrefix] || slugPrefix
}

// Build module-grouped permission tree
const permissionModules = computed<PermissionModule[]>(() => {
  const groups: Record<string, Permission[]> = {}

  for (const perm of allPermissions.value) {
    const prefix = perm.slug.split('.')[0] || 'other'
    if (!groups[prefix]) {
      groups[prefix] = []
    }
    groups[prefix].push(perm)
  }

  return Object.entries(groups)
    .map(([key, children]) => ({
      id: key,
      name: getModuleName(key),
      children: children.sort((a, b) => a.slug.localeCompare(b.slug)),
    }))
    .sort((a, b) => a.id.localeCompare(b.id))
})

// Filter modules by search text
const filteredModules = computed<PermissionModule[]>(() => {
  if (!filterText.value) return permissionModules.value

  const keyword = filterText.value.toLowerCase()
  return permissionModules.value
    .map((mod) => ({
      ...mod,
      children: mod.children.filter(
        (p) =>
          p.name.toLowerCase().includes(keyword) ||
          p.slug.toLowerCase().includes(keyword) ||
          (p.http_path && p.http_path.toLowerCase().includes(keyword))
      ),
    }))
    .filter((mod) => mod.children.length > 0)
})

// Module tri-state helpers
function isModuleAllSelected(module: PermissionModule): boolean {
  return module.children.length > 0 && module.children.every((p) => selectedMap.value[p.hash_id])
}

function isModuleIndeterminate(module: PermissionModule): boolean {
  const checked = module.children.filter((p) => selectedMap.value[p.hash_id]).length
  return checked > 0 && checked < module.children.length
}

function handleModuleCheck(module: PermissionModule, val: boolean | string | number) {
  const checked = !!val
  const newMap = { ...selectedMap.value }
  for (const perm of module.children) {
    newMap[perm.hash_id] = checked
  }
  selectedMap.value = newMap
}

function handlePermCheck(hashId: string, val: boolean | string | number) {
  selectedMap.value = { ...selectedMap.value, [hashId]: !!val }
}

// Global select all / unselect all
function checkAll() {
  const newMap: Record<string, boolean> = {}
  for (const perm of allPermissions.value) {
    newMap[perm.hash_id] = true
  }
  selectedMap.value = newMap
}

function uncheckAll() {
  const newMap: Record<string, boolean> = {}
  for (const perm of allPermissions.value) {
    newMap[perm.hash_id] = false
  }
  selectedMap.value = newMap
}

function methodTagType(method: string): 'primary' | 'success' | 'warning' | 'info' | 'danger' | undefined {
  const map: Record<string, 'primary' | 'success' | 'warning' | 'info' | 'danger'> = {
    GET: 'success',
    POST: 'warning',
    PUT: 'info',
    DELETE: 'danger',
    PATCH: 'primary',
  }
  return map[method?.toUpperCase()] || undefined
}

// Load data when dialog opens
watch(
  () => props.visible,
  async (val) => {
    if (val && props.roleData) {
      dialogLoading.value = true
      filterText.value = ''
      try {
        const [allPerms, rolePerms] = await Promise.all([
          getPermissionsList(),
          getRolePermissions(props.roleData.hash_id),
        ])

        allPermissions.value = allPerms as Permission[]

        // Build selected map from role's assigned permissions
        const newMap: Record<string, boolean> = {}
        for (const perm of allPermissions.value) {
          newMap[perm.hash_id] = false
        }

        const assigned = (rolePerms as any[]) || []
        for (const item of assigned) {
          const id = typeof item === 'string' ? item : item.hash_id
          if (id && newMap[id] !== undefined) {
            newMap[id] = true
          }
        }
        selectedMap.value = newMap
      } catch (error: any) {
        ElMessage.error(error?.message || t('common.operationFailed'))
      } finally {
        dialogLoading.value = false
      }
    }
  }
)

function handleClosed() {
  allPermissions.value = []
  selectedMap.value = {}
  filterText.value = ''
}

async function handleSubmit() {
  if (!props.roleData) return

  submitLoading.value = true
  try {
    const permissionIds = Object.entries(selectedMap.value)
      .filter(([, checked]) => checked)
      .map(([id]) => id)

    await assignPermissionsToRole(props.roleData.hash_id, {
      permission_ids: permissionIds,
    })
    ElMessage.success(t('role.permissionAssignSuccess'))
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
.permission-container {
  min-height: 200px;
}
.permission-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.toolbar-actions {
  display: flex;
  gap: 8px;
}
.permission-list {
  max-height: 450px;
  overflow-y: auto;
  border: 1px solid var(--el-border-color-light);
  border-radius: 4px;
  padding: 8px;
}
.permission-module {
  margin-bottom: 12px;
}
.permission-module:last-child {
  margin-bottom: 0;
}
.module-header {
  padding: 8px 4px;
  background-color: var(--el-fill-color-light);
  border-radius: 4px;
  margin-bottom: 4px;
}
.module-header :deep(.el-checkbox__label) {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  font-size: 14px;
}
.module-name {
  font-weight: 600;
}
.module-count {
  margin-left: 4px;
}
.module-items {
  padding-left: 25px;
}
.permission-item {
  padding: 5px 0;
  border-bottom: 1px solid var(--el-border-color-extra-light);
}
.permission-item:last-child {
  border-bottom: none;
}
.permission-item :deep(.el-checkbox) {
  width: 100%;
  height: auto;
}
.permission-item :deep(.el-checkbox__label) {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.perm-name {
  font-weight: 500;
  min-width: 80px;
}
.perm-slug {
  font-family: monospace;
}
.perm-method {
  min-width: 48px;
  text-align: center;
}
.perm-path {
  color: var(--el-text-color-secondary);
  font-size: 12px;
  font-family: monospace;
}
</style>
