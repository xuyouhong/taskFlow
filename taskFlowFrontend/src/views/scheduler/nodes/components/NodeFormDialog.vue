<template>
  <el-dialog v-model="visible" v-draggable :title="editData ? t('scheduler.editNode') : t('scheduler.createNode')" width="500px" @close="handleClose">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
      <el-form-item :label="t('scheduler.nodeName')" prop="name">
        <el-input v-model="form.name" :placeholder="t('scheduler.nodeNamePlaceholderInput')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.nodeIp')" prop="ip">
        <el-input v-model="form.ip" :placeholder="t('scheduler.nodeIpPlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.nodeAgentPort')" prop="agent_port">
        <el-input-number v-model="form.agent_port" :min="1" :max="65535" />
      </el-form-item>
      <el-form-item :label="t('scheduler.nodeHostname')" prop="hostname">
        <el-input v-model="form.hostname" :placeholder="t('scheduler.nodeHostnamePlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.nodeAgentToken')" prop="agent_token">
        <el-input v-model="form.agent_token" :placeholder="t('scheduler.nodeAgentTokenPlaceholder')" :disabled="!!editData" />
      </el-form-item>
      <el-form-item :label="t('scheduler.nodeAllowedCommandPrefix')" prop="allowed_command_prefix">
        <el-input v-model="form.allowed_command_prefix" :placeholder="t('scheduler.nodeAllowedCommandPrefixPlaceholder')" />
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
import type { Node } from '@/api/node'
import { createNode, updateNode } from '@/api/node'

const { t } = useI18n()

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
  name: [{ required: true, message: () => t('scheduler.nodeNameRequired'), trigger: 'blur' }],
  ip: [{ required: true, message: () => t('scheduler.nodeIpRequired'), trigger: 'blur' }],
  agent_token: [{ required: true, message: () => t('scheduler.nodeAgentTokenRequired'), trigger: 'blur' }],
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
      ElMessage.success(t('scheduler.updateSuccess'))
    } else {
      await createNode(form.value)
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
