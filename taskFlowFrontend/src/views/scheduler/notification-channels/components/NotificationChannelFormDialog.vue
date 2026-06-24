<template>
  <el-dialog v-model="visible" v-draggable :title="editData ? '编辑渠道' : '创建渠道'" width="500px" @close="handleClose">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
      <el-form-item label="渠道名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入渠道名称" />
      </el-form-item>
      <el-form-item label="类型" prop="type">
        <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%">
          <el-option label="Email" value="email" />
          <el-option label="Webhook" value="webhook" />
          <el-option label="钉钉" value="dingtalk" />
          <el-option label="企业微信" value="wecom" />
          <el-option label="飞书" value="feishu" />
        </el-select>
      </el-form-item>
      <el-form-item label="配置" prop="config">
        <el-input v-model="configJson" type="textarea" rows="4" placeholder='如: {"to": ["admin@example.com"]}' @blur="handleConfigBlur" />
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
import type { NotificationChannel } from '@/api/notificationChannel'
import { createNotificationChannel, updateNotificationChannel } from '@/api/notificationChannel'

const props = defineProps<{
  visible: boolean
  editData: NotificationChannel | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'submit': []
}>()

const formRef = ref<FormInstance>()
const submitting = ref(false)
const configJson = ref('')

const form = ref({
  name: '',
  type: 'email' as 'email' | 'webhook' | 'dingtalk' | 'wecom' | 'feishu',
  config: {} as any,
  status: 1 as number,
})

const rules: FormRules = {
  name: [{ required: true, message: '请输入渠道名称', trigger: 'blur' }],
  type: [{ required: true, message: '请选择类型', trigger: 'change' }],
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
        type: props.editData.type,
        config: props.editData.config || {},
        status: props.editData.status,
      }
      configJson.value = JSON.stringify(props.editData.config, null, 2)
    } else {
      form.value = { name: '', type: 'email', config: { to: [] }, status: 1 }
      configJson.value = JSON.stringify({ to: [] }, null, 2)
    }
  }
})

function handleConfigBlur() {
  try {
    form.value.config = JSON.parse(configJson.value)
  } catch {
    ElMessage.error('JSON格式错误')
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
    handleConfigBlur()
    if (props.editData) {
      await updateNotificationChannel(props.editData.hash_id, form.value)
      ElMessage.success('更新成功')
    } else {
      await createNotificationChannel(form.value)
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
