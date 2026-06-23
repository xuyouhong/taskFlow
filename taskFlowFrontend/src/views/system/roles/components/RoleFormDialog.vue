<template>
  <el-dialog
    :model-value="visible"
    :title="editData ? t('role.editRole') : t('role.createRole')"
    width="560px"
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
      <el-form-item :label="t('role.name')" prop="name">
        <el-input
          v-model="formData.name"
          :placeholder="t('role.namePlaceholder')"
          clearable
        />
      </el-form-item>

      <el-form-item :label="t('role.slug')" prop="slug">
        <el-input
          v-model="formData.slug"
          :placeholder="t('role.slugPlaceholder')"
          clearable
        />
      </el-form-item>

      <el-form-item :label="t('common.description')" prop="description">
        <el-input
          v-model="formData.description"
          type="textarea"
          :rows="3"
          :placeholder="t('role.descriptionPlaceholder')"
        />
      </el-form-item>

      <el-form-item :label="t('common.status')" prop="status">
        <el-radio-group v-model="formData.status">
          <el-radio :value="1">{{ t('common.enable') }}</el-radio>
          <el-radio :value="0">{{ t('common.disable') }}</el-radio>
        </el-radio-group>
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
    </el-form>

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
import { ref, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import type { Role } from '@/types/api'
import { createRole, updateRole } from '@/api/role'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  editData: Role | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  submit: []
}>()

const formRef = ref<FormInstance>()
const submitLoading = ref(false)

const getDefaultForm = () => ({
  name: '',
  slug: '',
  description: '',
  status: 1,
  sort: 0,
})

const formData = reactive(getDefaultForm())

const rules = reactive<FormRules>({
  name: [{ required: true, message: t('role.nameRequired'), trigger: 'blur' }],
  slug: [{ required: true, message: t('role.slugRequired'), trigger: 'blur' }],
  sort: [{ required: true, message: t('role.sortRequired'), trigger: 'blur' }],
})

watch(
  () => props.visible,
  (val) => {
    if (val) {
      Object.assign(formData, getDefaultForm())
      formRef.value?.clearValidate()
      if (props.editData) {
        Object.assign(formData, {
          name: props.editData.name,
          slug: props.editData.slug,
          description: props.editData.description || '',
          status: props.editData.status,
          sort: props.editData.sort,
        })
      }
    }
  }
)

function handleClosed() {
  Object.assign(formData, getDefaultForm())
  formRef.value?.resetFields()
}

async function handleSubmit() {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    if (props.editData) {
      await updateRole(props.editData.hash_id, { ...formData })
      ElMessage.success(t('common.updateSuccess'))
    } else {
      await createRole({ ...formData })
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
