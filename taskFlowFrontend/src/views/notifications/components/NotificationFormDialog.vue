<template>
  <el-dialog
    :model-value="visible"
    :title="isEdit ? t('notification.editNotification') : t('notification.createNotification')"
    width="900px"
    draggable
    destroy-on-close
    @update:model-value="$emit('update:visible', $event)"
    @opened="onOpened"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="110px"
      @submit.prevent
    >
      <!-- Title -->
      <el-form-item :label="t('notification.notificationTitle')" prop="title">
        <el-input
          v-model="form.title"
          :placeholder="t('notification.titlePlaceholder')"
          maxlength="200"
          show-word-limit
        />
      </el-form-item>

      <!-- Content -->
      <el-form-item :label="t('notification.content')" prop="content">
        <WangEditor
          v-model="form.content"
          :placeholder="t('notification.contentPlaceholder')"
          :min-height="300"
          :max-height="500"
        />
      </el-form-item>

      <!-- Type -->
      <el-form-item :label="t('notification.type')" prop="type">
        <el-radio-group v-model="form.type">
          <el-radio :value="1">{{ t('notification.typeNotification') }}</el-radio>
          <el-radio :value="2">{{ t('notification.typeAnnouncement') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <!-- Priority -->
      <el-form-item :label="t('notification.priority')" prop="priority">
        <el-radio-group v-model="form.priority">
          <el-radio :value="1">{{ t('notification.priorityNormal') }}</el-radio>
          <el-radio :value="2">{{ t('notification.priorityImportant') }}</el-radio>
          <el-radio :value="3">{{ t('notification.priorityUrgent') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <!-- Target Type -->
      <el-form-item :label="t('notification.targetType')" prop="target_type">
        <el-radio-group v-model="form.target_type" @change="onTargetTypeChange">
          <el-radio :value="1">{{ t('notification.targetAll') }}</el-radio>
          <el-radio :value="2">{{ t('notification.targetRoles') }}</el-radio>
          <el-radio :value="3">{{ t('notification.targetUsers') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <!-- Target Values: Roles -->
      <el-form-item
        v-if="form.target_type === 2"
        :label="t('notification.targetValues')"
        prop="target_values"
      >
        <el-select
          v-model="form.target_values"
          multiple
          filterable
          :loading="rolesLoading"
          :placeholder="t('notification.selectRoles')"
          style="width: 100%"
        >
          <el-option
            v-for="role in roleOptions"
            :key="role.hash_id"
            :label="role.name"
            :value="role.hash_id"
          />
        </el-select>
      </el-form-item>

      <!-- Target Values: Users -->
      <el-form-item
        v-if="form.target_type === 3"
        :label="t('notification.targetValues')"
        prop="target_values"
      >
        <el-select
          v-model="form.target_values"
          multiple
          filterable
          remote
          reserve-keyword
          :loading="usersLoading"
          :remote-method="searchUsers"
          :placeholder="t('notification.selectUsers')"
          style="width: 100%"
        >
          <el-option
            v-for="user in userOptions"
            :key="user.hash_id"
            :label="user.real_name || user.username"
            :value="user.hash_id"
          />
        </el-select>
      </el-form-item>

      <!-- Publish Time -->
      <el-form-item :label="t('notification.publishTime')" prop="publish_time">
        <el-date-picker
          v-model="form.publish_time"
          type="datetime"
          value-format="YYYY-MM-DD HH:mm:ss"
          style="width: 100%"
        />
      </el-form-item>

      <!-- Expire Time -->
      <el-form-item :label="t('notification.expireTime')" prop="expire_time">
        <el-date-picker
          v-model="form.expire_time"
          type="datetime"
          value-format="YYYY-MM-DD HH:mm:ss"
          style="width: 100%"
        />
      </el-form-item>

      <!-- Status -->
      <el-form-item :label="t('notification.status')" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio :value="1">{{ t('notification.statusDraft') }}</el-radio>
          <el-radio :value="2">{{ t('notification.statusPublished') }}</el-radio>
        </el-radio-group>
      </el-form-item>
    </el-form>

    <template #footer>
      <el-button @click="$emit('update:visible', false)">
        {{ t('common.cancel') }}
      </el-button>
      <el-button type="primary" :loading="submitting" @click="handleSubmit">
        {{ t('common.confirm') }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import {
  createAdminNotification,
  updateAdminNotification,
} from '@/api/notification'
import { getUserRoles, getUsers } from '@/api/user'
import type { AdminNotification, Role, User } from '@/types/api'
import WangEditor from '@/components/WangEditor/index.vue'

const props = defineProps<{
  visible: boolean
  editData: AdminNotification | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  submit: []
}>()

const { t } = useI18n()

const isEdit = computed(() => !!props.editData)

// ---------- Form ----------
interface NotificationForm {
  title: string
  content: string
  type: number
  priority: number
  target_type: number
  target_values: string[]
  publish_time: string
  expire_time: string
  status: number
}

const defaultForm: NotificationForm = {
  title: '',
  content: '',
  type: 1,
  priority: 1,
  target_type: 1,
  target_values: [],
  publish_time: '',
  expire_time: '',
  status: 1,
}

const formRef = ref<FormInstance>()
const form = reactive<NotificationForm>({ ...defaultForm })
const submitting = ref(false)

// ---------- Validation ----------
const targetValuesValidator = (_rule: any, value: any, callback: any) => {
  if (form.target_type === 1) {
    callback()
    return
  }
  if (!value || value.length === 0) {
    callback(new Error(
      form.target_type === 2
        ? t('notification.selectRoles')
        : t('notification.selectUsers')
    ))
    return
  }
  callback()
}

const rules = reactive<FormRules>({
  title: [
    { required: true, message: t('notification.titleRequired'), trigger: 'blur' },
  ],
  content: [
    { required: true, message: t('notification.contentRequired'), trigger: 'change' },
  ],
  type: [
    { required: true, message: t('notification.typeRequired'), trigger: 'change' },
  ],
  priority: [
    { required: true, message: t('notification.priorityRequired'), trigger: 'change' },
  ],
  target_type: [
    { required: true, message: t('notification.targetTypeRequired'), trigger: 'change' },
  ],
  target_values: [
    { validator: targetValuesValidator, trigger: 'change' },
  ],
})

// ---------- Role options ----------
const roleOptions = ref<Pick<Role, 'hash_id' | 'name'>[]>([])
const rolesLoading = ref(false)

async function loadRoles() {
  if (roleOptions.value.length > 0) return
  rolesLoading.value = true
  try {
    const res = await getUserRoles()
    roleOptions.value = Array.isArray(res) ? res : []
  } catch {
    // ignore
  } finally {
    rolesLoading.value = false
  }
}

// ---------- User options ----------
const userOptions = ref<Pick<User, 'hash_id' | 'username' | 'real_name'>[]>([])
const usersLoading = ref(false)

async function searchUsers(query?: string) {
  usersLoading.value = true
  try {
    const res = await getUsers({ page: 1, per_page: 50, username: query || '' } as any)
    const data = res as any
    userOptions.value = data?.list || []
  } catch {
    // ignore
  } finally {
    usersLoading.value = false
  }
}

// ---------- Target type change ----------
function onTargetTypeChange() {
  form.target_values = []
  if (form.target_type === 2) {
    loadRoles()
  } else if (form.target_type === 3) {
    searchUsers()
  }
}

// ---------- Populate on open ----------
function onOpened() {
  resetForm()
  if (props.editData) {
    form.title = props.editData.title
    form.content = props.editData.content
    form.type = props.editData.type
    form.priority = props.editData.priority
    form.target_type = props.editData.target_type
    form.target_values = props.editData.target_values ? [...props.editData.target_values] : []
    form.publish_time = props.editData.publish_time || ''
    form.expire_time = props.editData.expire_time || ''
    form.status = props.editData.status

    // Pre-load options for edit mode
    if (form.target_type === 2) loadRoles()
    if (form.target_type === 3) searchUsers()
  }
}

function resetForm() {
  Object.keys(defaultForm).forEach((key) => {
    (form as any)[key] =
      key === 'target_values'
        ? []
        : (defaultForm as any)[key]
  })
  formRef.value?.clearValidate()
}

// ---------- Submit ----------
async function handleSubmit() {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
  } catch {
    return
  }

  const payload: Record<string, any> = {
    title: form.title,
    content: form.content,
    type: form.type,
    priority: form.priority,
    target_type: form.target_type,
    target_values: form.target_type === 1 ? null : form.target_values,
    publish_time: form.publish_time || null,
    expire_time: form.expire_time || null,
    status: form.status,
  }

  submitting.value = true
  try {
    if (isEdit.value && props.editData) {
      await updateAdminNotification(props.editData.hash_id, payload)
      ElMessage.success(t('common.updateSuccess'))
    } else {
      await createAdminNotification(payload)
      ElMessage.success(t('common.createSuccess'))
    }
    emit('submit')
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  } finally {
    submitting.value = false
  }
}
</script>
