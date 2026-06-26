<template>
  <el-dialog v-model="visible" v-draggable :title="editData ? t('scheduler.editTask') : t('scheduler.createTask')" width="650px" @close="handleClose">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
      <el-form-item :label="t('scheduler.belongsToProject')" prop="project_id">
        <el-select v-model="form.project_id" :placeholder="t('scheduler.projectPlaceholder')" style="width: 100%">
          <el-option v-for="p in projects" :key="p.hash_id" :label="p.name" :value="p.hash_id" />
        </el-select>
      </el-form-item>
      <el-form-item :label="t('scheduler.taskName')" prop="name">
        <el-input v-model="form.name" :placeholder="t('scheduler.taskNamePlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.description')" prop="description">
        <el-input v-model="form.description" type="textarea" rows="2" :placeholder="t('scheduler.descriptionPlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.cronExpression')" prop="cron_expression">
        <el-input v-model="form.cron_expression" :placeholder="t('scheduler.cronExpressionPlaceholder')" />
      </el-form-item>
      <el-form-item :label="t('scheduler.timezone')" prop="timezone">
        <el-select v-model="form.timezone" :placeholder="t('scheduler.timezone')" style="width: 100%">
          <el-option label="Asia/Shanghai" value="Asia/Shanghai" />
          <el-option label="UTC" value="UTC" />
          <el-option label="America/New_York" value="America/New_York" />
        </el-select>
      </el-form-item>
      <el-form-item :label="t('scheduler.executorType')" prop="executor_type">
        <el-radio-group v-model="form.executor_type">
          <el-radio label="http">HTTP</el-radio>
          <el-radio label="shell">Shell</el-radio>
          <el-radio label="job">Job</el-radio>
          <el-radio label="mq">MQ</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item :label="t('scheduler.executorConfig')" prop="executor_config">
        <el-input v-model="executorConfigJson" type="textarea" rows="3" :placeholder="t('scheduler.executorConfigPlaceholder')" @blur="handleConfigBlur" />
      </el-form-item>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item :label="t('scheduler.timeout')" prop="timeout">
            <el-input-number v-model="form.timeout" :min="1" :max="86400" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item :label="t('scheduler.retryTimes')" prop="retry_times">
            <el-input-number v-model="form.retry_times" :min="0" :max="10" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item :label="t('scheduler.retryInterval')" prop="retry_interval">
            <el-input-number v-model="form.retry_interval" :min="1" :max="3600" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item :label="t('scheduler.priority')" prop="priority">
            <el-input-number v-model="form.priority" :min="-100" :max="100" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item :label="t('scheduler.concurrencyStrategy')" prop="concurrency_strategy">
            <el-select v-model="form.concurrency_strategy" style="width: 100%">
              <el-option :label="t('scheduler.concurrencyAllow')" value="allow" />
              <el-option :label="t('scheduler.concurrencyForbid')" value="forbid" />
              <el-option :label="t('scheduler.concurrencyReplace')" value="replace" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item :label="t('scheduler.misfireStrategy')" prop="misfire_strategy">
            <el-select v-model="form.misfire_strategy" style="width: 100%">
              <el-option :label="t('scheduler.misfireSkip')" value="skip" />
              <el-option :label="t('scheduler.misfireFireOnce')" value="fire_once" />
              <el-option :label="t('scheduler.misfireFireAll')" value="fire_all" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item :label="t('scheduler.status')" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio label="enabled">{{ t('scheduler.statusEnabled') }}</el-radio>
          <el-radio label="disabled">{{ t('scheduler.statusDisabled') }}</el-radio>
          <el-radio label="paused">{{ t('scheduler.statusPaused') }}</el-radio>
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
import type { Task } from '@/api/task'
import { createTask, updateTask } from '@/api/task'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  editData: Task | null
  projects: any[]
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  'submit': []
}>()

const formRef = ref<FormInstance>()
const submitting = ref(false)

const form = ref({
  project_id: '',
  name: '',
  description: '',
  cron_expression: '*/5 * * * *',
  timezone: 'Asia/Shanghai',
  executor_type: 'http' as 'http' | 'shell' | 'job' | 'mq',
  executor_config: {} as any,
  retry_times: 0,
  retry_interval: 60,
  timeout: 300,
  concurrency_strategy: 'forbid' as 'allow' | 'forbid' | 'replace',
  misfire_strategy: 'skip' as 'skip' | 'fire_once' | 'fire_all',
  priority: 0,
  status: 'enabled' as 'enabled' | 'disabled' | 'paused',
})

const executorConfigJson = ref('')

const rules: FormRules = {
  project_id: [{ required: true, message: () => t('scheduler.projectRequired'), trigger: 'change' }],
  name: [{ required: true, message: () => t('scheduler.taskNameRequired'), trigger: 'blur' }],
  cron_expression: [{ required: true, message: () => t('scheduler.cronRequired'), trigger: 'blur' }],
  executor_type: [{ required: true, message: () => t('scheduler.executorTypeRequired'), trigger: 'change' }],
}

const visible = computed({
  get: () => props.visible,
  set: (val) => emit('update:visible', val),
})

watch(() => props.visible, (val) => {
  if (val) {
    if (props.editData) {
      form.value = {
        project_id: props.editData.project_id,
        name: props.editData.name,
        description: props.editData.description || '',
        cron_expression: props.editData.cron_expression,
        timezone: props.editData.timezone,
        executor_type: props.editData.executor_type,
        executor_config: props.editData.executor_config || {},
        retry_times: props.editData.retry_times,
        retry_interval: props.editData.retry_interval,
        timeout: props.editData.timeout,
        concurrency_strategy: props.editData.concurrency_strategy,
        misfire_strategy: props.editData.misfire_strategy,
        priority: props.editData.priority,
        status: props.editData.status,
      }
      executorConfigJson.value = JSON.stringify(props.editData.executor_config, null, 2)
    } else {
      form.value = {
        project_id: props.projects[0]?.hash_id || '',
        name: '',
        description: '',
        cron_expression: '* * * * * *',
        timezone: 'Asia/Shanghai',
        executor_type: 'http',
        executor_config: {},
        retry_times: 0,
        retry_interval: 60,
        timeout: 300,
        concurrency_strategy: 'forbid',
        misfire_strategy: 'skip',
        priority: 0,
        status: 'enabled',
      }
      executorConfigJson.value = '{}'
    }
  }
})

function handleConfigBlur() {
  try {
    form.value.executor_config = JSON.parse(executorConfigJson.value)
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
  try {
    handleConfigBlur()
    submitting.value = true
    if (props.editData) {
      await updateTask(props.editData.hash_id, form.value)
      ElMessage.success(t('scheduler.updateSuccess'))
    } else {
      await createTask(form.value)
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
