<template>
  <el-dialog
    :model-value="visible"
    :title="editData ? t('menu.editMenu') : t('menu.createMenu')"
    width="620px"
    draggable
    :close-on-click-modal="false"
    :destroy-on-close="true"
    @update:model-value="$emit('update:visible', $event)"
    @closed="handleClosed"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="rules"
      label-width="100px"
      @submit.prevent
    >
      <el-form-item :label="t('menu.parentId')" prop="parent_id">
        <el-tree-select
          v-model="formData.parent_id"
          :data="menuTreeData"
          :props="{ label: 'name', children: 'children', value: 'hash_id' }"
          :placeholder="t('menu.parentIdPlaceholder')"
          node-key="hash_id"
          check-strictly
          clearable
          :render-after-expand="false"
          style="width: 100%"
        >
          <template #empty>
            <span>{{ t('common.noData') }}</span>
          </template>
        </el-tree-select>
      </el-form-item>

      <el-form-item :label="t('menu.name')" prop="name">
        <el-input
          v-model="formData.name"
          :placeholder="t('menu.namePlaceholder')"
          clearable
        />
      </el-form-item>

      <el-form-item :label="t('menu.icon')" prop="icon">
        <div style="display: flex; align-items: center; gap: 8px; width: 100%;">
          <el-input
            v-model="formData.icon"
            :placeholder="t('menu.iconPlaceholder')"
            clearable
            style="flex: 1"
          >
            <template #prefix>
              <el-icon v-if="formData.icon">
                <component :is="formData.icon" />
              </el-icon>
            </template>
          </el-input>
          <el-button @click="iconSelectorVisible = true">
            {{ t('menu.selectIcon') }}
          </el-button>
        </div>
      </el-form-item>

      <el-form-item :label="t('menu.type')" prop="type">
        <el-radio-group v-model="formData.type">
          <el-radio :value="1">{{ t('menu.typeDirectory') }}</el-radio>
          <el-radio :value="2">{{ t('menu.typeMenu') }}</el-radio>
          <el-radio :value="3">{{ t('menu.typeButton') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item
        v-if="formData.type === 2"
        :label="t('menu.path')"
        prop="path"
      >
        <el-input
          v-model="formData.path"
          :placeholder="t('menu.pathPlaceholder')"
          clearable
        />
      </el-form-item>

      <el-form-item
        v-if="formData.type === 2"
        :label="t('menu.component')"
        prop="component"
      >
        <el-input
          v-model="formData.component"
          :placeholder="t('menu.componentPlaceholder')"
          clearable
        />
      </el-form-item>

      <el-form-item :label="t('common.sort')" prop="sort">
        <el-input-number
          v-model="formData.sort"
          :min="0"
          :max="9999"
          controls-position="right"
          style="width: 100%"
        />
      </el-form-item>

      <el-form-item :label="t('common.status')" prop="status">
        <el-radio-group v-model="formData.status">
          <el-radio :value="1">{{ t('common.enable') }}</el-radio>
          <el-radio :value="0">{{ t('common.disable') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item :label="t('menu.isLink')" prop="is_link">
        <el-radio-group v-model="formData.is_link">
          <el-radio :value="0">{{ t('menu.isLinkNo') }}</el-radio>
          <el-radio :value="1">{{ t('menu.isLinkYes') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item :label="t('menu.keepAlive')" prop="keep_alive">
        <el-radio-group v-model="formData.keep_alive">
          <el-radio :value="0">{{ t('menu.keepAliveNo') }}</el-radio>
          <el-radio :value="1">{{ t('menu.keepAliveYes') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item :label="t('common.description')" prop="description">
        <el-input
          v-model="formData.description"
          type="textarea"
          :rows="3"
          :placeholder="t('common.description')"
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('update:visible', false)">
        {{ t('common.cancel') }}
      </el-button>
      <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
        {{ t('common.confirm') }}
      </el-button>
    </template>

    <!-- Icon Selector Dialog -->
    <IconSelector
      v-model:visible="iconSelectorVisible"
      @select="handleIconSelect"
    />
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { createMenu, updateMenu, getMenusTree } from '@/api/menu'
import type { Menu } from '@/types/api'
import IconSelector from './IconSelector.vue'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  editData: Menu | null
  parentId: string | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  submit: []
}>()

const formRef = ref<FormInstance>()
const submitLoading = ref(false)
const menuTreeData = ref<Menu[]>([])
const iconSelectorVisible = ref(false)

const getDefaultForm = () => ({
  parent_id: null as string | null,
  name: '',
  icon: '',
  path: '',
  component: '',
  sort: 0,
  type: 1 as number,
  status: 1,
  is_link: 0,
  keep_alive: 0,
  description: '',
})

const formData = reactive(getDefaultForm())

const rules = reactive<FormRules>({
  name: [{ required: true, message: t('menu.nameRequired'), trigger: 'blur' }],
  type: [{ required: true, message: t('menu.typeRequired'), trigger: 'change' }],
  sort: [{ required: true, message: t('menu.sortRequired'), trigger: 'blur' }],
})

// Load menu tree for the parent selector
async function loadMenuTree() {
  try {
    const res = await getMenusTree()
    // Prepend a "Root Menu" node with hash_id = '' (will be sent as null)
    menuTreeData.value = [
      {
        hash_id: '',
        parent_id: null,
        name: t('menu.rootMenu'),
        icon: '',
        path: '',
        component: '',
        sort: 0,
        type: 1,
        status: 1,
        is_link: 0,
        keep_alive: 0,
        description: '',
        created_at: '',
        updated_at: '',
        children: res || [],
      },
    ]
  } catch {
    menuTreeData.value = []
  }
}

watch(
  () => props.visible,
  (val) => {
    if (val) {
      loadMenuTree()
      Object.assign(formData, getDefaultForm())
      formRef.value?.clearValidate()
      if (props.editData) {
        Object.assign(formData, {
          parent_id: props.editData.parent_id ?? '',
          name: props.editData.name,
          icon: props.editData.icon,
          path: props.editData.path,
          component: props.editData.component,
          sort: props.editData.sort,
          type: props.editData.type,
          status: props.editData.status,
          is_link: props.editData.is_link,
          keep_alive: props.editData.keep_alive,
          description: props.editData.description,
        })
      } else if (props.parentId) {
        // Adding a child menu
        formData.parent_id = props.parentId
      }
    }
  }
)

function handleClosed() {
  Object.assign(formData, getDefaultForm())
  formRef.value?.resetFields()
}

function handleIconSelect(name: string) {
  formData.icon = name
}

async function handleSubmit() {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    // Convert empty parent_id string to null
    const payload = {
      ...formData,
      parent_id: formData.parent_id === '' || !formData.parent_id ? null : formData.parent_id,
    }

    if (props.editData) {
      await updateMenu(props.editData.hash_id, payload)
      ElMessage.success(t('common.updateSuccess'))
    } else {
      await createMenu(payload)
      ElMessage.success(t('common.createSuccess'))
    }
    emit('update:visible', false)
    emit('submit')
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  } finally {
    submitLoading.value = false
  }
}
</script>
