<template>
  <el-dialog v-model="visible" v-draggable :title="editData ? '编辑任务' : '创建任务'" width="650px" @close="handleClose">
    <el-form ref="formRef" :model="form" :rules="rules" label-width="110px">
      <el-form-item label="所属项目" prop="project_id">
        <el-select v-model="form.project_id" placeholder="请选择项目" style="width: 100%">
          <el-option v-for="p in projects" :key="p.hash_id" :label="p.name" :value="p.hash_id" />
        </el-select>
      </el-form-item>
      <el-form-item label="任务名称" prop="name">
        <el-input v-model="form.name" placeholder="请输入任务名称" />
      </el-form-item>
      <el-form-item label="描述" prop="description">
        <el-input v-model="form.description" type="textarea" rows="2" placeholder="请输入描述" />
      </el-form-item>
      <el-form-item label="Cron表达式" prop="cron_expression">
        <el-input v-model="form.cron_expression" placeholder="如: */5 * * * * (分 时 日 月 周) 或 */30 * * * * * (秒 分 时 日 月 周)" />
      </el-form-item>
      <el-form-item label="时区" prop="timezone">
        <el-select v-model="form.timezone" placeholder="请选择时区" style="width: 100%">
          <el-option label="Asia/Shanghai" value="Asia/Shanghai" />
          <el-option label="UTC" value="UTC" />
          <el-option label="America/New_York" value="America/New_York" />
        </el-select>
      </el-form-item>
      <el-form-item label="执行器类型" prop="executor_type">
        <el-radio-group v-model="form.executor_type">
          <el-radio label="http">HTTP</el-radio>
          <el-radio label="shell">Shell</el-radio>
          <el-radio label="job">Job</el-radio>
          <el-radio label="mq">MQ</el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item label="执行配置" prop="executor_config">
        <el-input v-model="executorConfigJson" type="textarea" rows="3" placeholder='如: {"url": "http://example.com/api"}' @blur="handleConfigBlur" />
      </el-form-item>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="超时时间(秒)" prop="timeout">
            <el-input-number v-model="form.timeout" :min="1" :max="86400" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="重试次数" prop="retry_times">
            <el-input-number v-model="form.retry_times" :min="0" :max="10" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="重试间隔(秒)" prop="retry_interval">
            <el-input-number v-model="form.retry_interval" :min="1" :max="3600" />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="优先级" prop="priority">
            <el-input-number v-model="form.priority" :min="-100" :max="100" />
          </el-form-item>
        </el-col>
      </el-row>
      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="并发策略" prop="concurrency_strategy">
            <el-select v-model="form.concurrency_strategy" style="width: 100%">
              <el-option label="允许并发" value="allow" />
              <el-option label="禁止并发" value="forbid" />
              <el-option label="替换执行" value="replace" />
            </el-select>
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="失火策略" prop="misfire_strategy">
            <el-select v-model="form.misfire_strategy" style="width: 100%">
              <el-option label="跳过" value="skip" />
              <el-option label="执行一次" value="fire_once" />
              <el-option label="执行全部" value="fire_all" />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>
      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio label="enabled">启用</el-radio>
          <el-radio label="disabled">禁用</el-radio>
          <el-radio label="paused">暂停</el-radio>
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
import type { Task } from '@/api/task'
import { createTask, updateTask } from '@/api/task'

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
  project_id: [{ required: true, message: '请选择项目', trigger: 'change' }],
  name: [{ required: true, message: '请输入任务名称', trigger: 'blur' }],
  cron_expression: [{ required: true, message: '请输入Cron表达式', trigger: 'blur' }],
  executor_type: [{ required: true, message: '请选择执行器类型', trigger: 'change' }],
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
    ElMessage.error('JSON格式错误')
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
      ElMessage.success('更新成功')
    } else {
      await createTask(form.value)
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
