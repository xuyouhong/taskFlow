<template>
  <el-dialog v-model="visible" v-draggable :title="editData ? '编辑节点' : '添加节点'" width="500px" @close="handleClose">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
      <el-form-item label="节点名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入节点名称" />
      </el-form-item>
      <el-form-item label="IP地址" prop="ip">
        <el-input v-model="form.ip" placeholder="请输入IP地址" />
      </el-form-item>
      <el-form-item label="Agent端口" prop="agent_port">
        <el-input-number v-model="form.agent_port" :min="1" :max="65535" />
      </el-form-item>
      <el-form-item label="主机名" prop="hostname">
        <el-input v-model="form.hostname" placeholder="请输入主机名" />
      </el-form-item>
      <el-form-item label="Agent Token" prop="agent_token">
        <el-input v-model="form.agent_token" placeholder="请输入Agent Token" :disabled="!!editData" />
      </el-form-item>
      <el-form-item label="命令白名单" prop="allowed_command_prefix">
        <el-input v-model="form.allowed_command_prefix" placeholder="如: /opt/scripts/" />
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
import type { Node } from '@/api/node'
import { createNode, updateNode } from '@/api/node'

const props = defineProps<{
  visible: boolean
  editData: Node | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'submit': []
}>()

const formRef = ref<FormInstance>()
const submitting = ref(false)

const form = ref({
  name: '',
  ip: '',
  agent_port: 9501,
  hostname: '',
  agent_token: '',
  allowed_command_prefix: '',
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入节点名称', trigger: 'blur' }],
  ip: [{ required: true, message: '请输入IP地址', trigger: 'blur' }],
  agent_token: [{ required: true, message: '请输入Agent Token', trigger: 'blur' }],
}

const visible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val),
})

watch(() => props.visible, (val) => {
  if (val) {
    if (props.editData) {
      form.value = {
        name: props.editData.name,
        ip: props.editData.ip,
        agent_port: props.editData.agent_port,
        hostname: props.editData.hostname || '',
        agent_token: props.editData.agent_token || '',
        allowed_command_prefix: props.editData.allowed_command_prefix || '',
      }
    } else {
      form.value = { name: '', ip: '', agent_port: 9501, hostname: '', agent_token: generateToken(), allowed_command_prefix: '' }
    }
  }
})

function generateToken() {
  return Array.from({ length: 32 }, () => Math.random().toString(36).charAt(2)).join('')
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
      await updateNode(props.editData.hash_id, form.value)
      ElMessage.success('更新成功')
    } else {
      await createNode(form.value)
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
