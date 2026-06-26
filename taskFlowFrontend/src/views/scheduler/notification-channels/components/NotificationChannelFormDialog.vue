<template>
  <el-dialog v-model="visible" v-draggable :title="editData ? t('scheduler.editChannel') : t('scheduler.createChannel')" width="500px" @close="handleClose">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="100px">
      <el-form-item :label="t('scheduler.channelName')" prop="name">
        <el-input v-model="form.name" :placeholder="t('scheduler.channelNamePlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.channelType')" prop="type">
        <el-select v-model="form.type" :placeholder="t('scheduler.channelTypePlaceholder')" style="width: 100%">
          <el-option :label="t('scheduler.typeEmail')" value="email" />
          <el-option :label="t('scheduler.typeWebhook')" value="webhook" />
          <el-option :label="t('scheduler.typeDingtalk')" value="dingtalk" />
          <el-option :label="t('scheduler.typeWecom')" value="wecom" />
          <el-option :label="t('scheduler.typeFeishu')" value="feishu" />
        </el-select>
      </el-form-item>
      <el-form-item :label="t('scheduler.channelConfig')" prop="config">
        <el-input v-model="configJson" type="textarea" rows="4" :placeholder="t('scheduler.channelConfigPlaceholder')" @blur="handleConfigBlur" />
      </el-form-item>
      <el-form-item :label="t('scheduler.channelStatus')" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio :label="1">{{ t('scheduler.channelEnabled') }}</el-radio>
          <el-radio :label="0">{{ t('scheduler.channelDisabled') }}</el-radio>
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
import type { NotificationChannel } from '@/api/notificationChannel'
import { createNotificationChannel, updateNotificationChannel } from '@/api/notificationChannel'

const { t } = useI18n()

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
  name: [{ required: true, message: () => t('scheduler.channelNameRequired'), trigger: 'blur' }],
  type: [{ required: true, message: () => t('scheduler.channelTypeRequired'), trigger: 'change' }],
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
    ElMessage.error(t('scheduler.jsonFormatError'))
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
      ElMessage.success(t('scheduler.updateSuccess'))
    } else {
      await createNotificationChannel(form.value)
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
