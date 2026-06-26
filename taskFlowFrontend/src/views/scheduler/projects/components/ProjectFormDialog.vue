<template>
  <el-dialog
    v-model="visible"
    v-draggable
    :title="editData ? t('scheduler.editProject') : t('scheduler.createProject')"
    width="500px"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
      <el-form-item :label="t('scheduler.projectName')" prop="name">
        <el-input v-model="form.name" :placeholder="t('scheduler.projectNamePlaceholderInput')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.projectCode')" prop="code">
        <el-input v-model="form.code" :placeholder="t('scheduler.projectCodePlaceholder')" :disabled="!!editData" />
      </el-form-item>
      <el-form-item :label="t('scheduler.projectDescription')" prop="description">
        <el-input v-model="form.description" type="textarea" rows="3" :placeholder="t('scheduler.projectDescriptionPlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.projectOwner')" prop="owner_id">
        <el-select v-model="form.owner_id" :placeholder="t('scheduler.projectOwnerPlaceholder')" filterable style="width: 100%">
          <el-option v-for="user in users" :key="user.hash_id" :label="user.username" :value="user.hash_id" />
        </el-select>
      </el-form-item>
      <el-form-item :label="t('scheduler.projectStatus')" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio :label="1">{{ t('scheduler.projectEnabled') }}</el-radio>
          <el-radio :label="0">{{ t('scheduler.projectDisabled') }}</el-radio>
        </el-radio-group>
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="handleClose">{{ t('common.cancel') }}</el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit">{{ t('common.confirm') }}</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import type { Project } from '@/api/project'
import { createProject, updateProject } from '@/api/project'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  editData: Project | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'submit': []
}>()

const formRef = ref<FormInstance>()
const submitting = ref(false)
const users = ref<any[]>([])

const form = ref({
  name: '',
  code: '',
  description: '',
  owner_id: '',
  status: 1,
})

const rules: FormRules = {
  name: [{ required: true, message: () => t('scheduler.projectNameRequired'), trigger: 'blur' }],
  code: [{ required: true, message: () => t('scheduler.projectCodeRequired'), trigger: 'blur' }],
  owner_id: [{ required: true, message: () => t('scheduler.projectOwnerRequired'), trigger: 'change' }],
}

const visible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val),
})

watch(() => props.visible, async (val) => {
  if (val) {
    await loadUsers()
    if (props.editData) {
      form.value = {
        name: props.editData.name,
        code: props.editData.code,
        description: props.editData.description || '',
        owner_id: props.editData.owner_id,
        status: props.editData.status,
      }
    } else {
      form.value = { name: '', code: '', description: '', owner_id: '', status: 1 }
    }
  }
})

async function loadUsers() {
  try {
    const authStore = useAuthStore()
    const user = authStore.userInfo
    if (user) {
      users.value = [user]
      if (!form.value.owner_id) {
        form.value.owner_id = user.hash_id
      }
    }
  } catch {
    users.value = []
  }
}

function handleClose() {
  formRef.value?.resetFields()
  visible.value = false
}

async function handleSubmit() {
  await formRef.value?.validate()
  submitting.value = true
  try {
    if (props.editData) {
      await updateProject(props.editData.hash_id, form.value)
      ElMessage.success(t('scheduler.updateSuccess'))
    } else {
      await createProject(form.value)
      ElMessage.success(t('scheduler.createSuccess'))
    }
    emit('submit')
    handleClose()
  } catch {
  } finally {
    submitting.value = false
  }
}
</script>
