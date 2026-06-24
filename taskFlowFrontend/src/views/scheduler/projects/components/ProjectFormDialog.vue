<template>
  <el-dialog
    v-model="visible"
    v-draggable
    :title="editData ? '编辑项目' : '创建项目'"
    width="500px"
    @close="handleClose"
  >
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
      <el-form-item label="项目名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入项目名称" />
      </el-form-item>
      <el-form-item label="项目编码" prop="code">
        <el-input v-model="form.code" placeholder="请输入项目编码" :disabled="!!editData" />
      </el-form-item>
      <el-form-item label="描述" prop="description">
        <el-input v-model="form.description" type="textarea" rows="3" placeholder="请输入描述" />
      </el-form-item>
      <el-form-item label="负责人" prop="owner_id">
        <el-select v-model="form.owner_id" placeholder="请选择负责人" filterable style="width: 100%">
          <el-option v-for="user in users" :key="user.hash_id" :label="user.username" :value="user.hash_id" />
        </el-select>
      </el-form-item>
      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio :label="1">启用</el-radio>
          <el-radio :label="0">禁用</el-radio>
        </el-radio-group>
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="handleClose">取消</el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit">确定</el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import type { Project } from '@/api/project'
import { createProject, updateProject } from '@/api/project'
import { useAuthStore } from '@/stores/auth'

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
  name: [{ required: true, message: '请输入项目名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入项目编码', trigger: 'blur' }],
  owner_id: [{ required: true, message: '请选择负责人', trigger: 'change' }],
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
      ElMessage.success('更新成功')
    } else {
      await createProject(form.value)
      ElMessage.success('创建成功')
    }
    emit('submit')
    handleClose()
  } catch {
  } finally {
    submitting.value = false
  }
}
</script>
