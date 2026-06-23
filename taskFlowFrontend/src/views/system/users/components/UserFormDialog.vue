<template>
  <el-dialog
    :model-value="visible"
    :title="editData ? t('user.editUser') : t('user.createUser')"
    width="600px"
    draggable
    :close-on-click-modal="false"
    :destroy-on-close="true"
    @update:model-value="$emit('update:visible', $event)"
    @closed="handleClosed"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="computedRules"
      label-width="100px"
      @submit.prevent
    >
      <el-form-item :label="t('user.username')" prop="username">
        <el-input
          v-model="formData.username"
          :placeholder="t('user.usernamePlaceholder')"
          :disabled="!!editData"
          maxlength="20"
          show-word-limit
        />
      </el-form-item>

      <el-form-item :label="t('user.password')" prop="password">
        <el-input
          v-model="formData.password"
          type="password"
          :placeholder="editData ? t('user.passwordOptionalPlaceholder') : t('user.passwordPlaceholder')"
          maxlength="20"
          show-word-limit
          show-password
        />
      </el-form-item>

      <el-form-item :label="t('user.confirmPassword')" prop="password_confirmation">
        <el-input
          v-model="formData.password_confirmation"
          type="password"
          :placeholder="editData ? t('user.confirmPasswordOptionalPlaceholder') : t('user.confirmPasswordPlaceholder')"
          maxlength="20"
          show-word-limit
          show-password
        />
      </el-form-item>

      <el-form-item :label="t('user.realName')" prop="real_name">
        <el-input
          v-model="formData.real_name"
          :placeholder="t('user.realNamePlaceholder')"
          maxlength="20"
          show-word-limit
        />
      </el-form-item>

      <el-form-item :label="t('user.email')" prop="email">
        <el-input
          v-model="formData.email"
          :placeholder="t('user.emailPlaceholder')"
          maxlength="50"
          show-word-limit
        />
      </el-form-item>

      <el-form-item :label="t('user.phone')" prop="phone">
        <el-input
          v-model="formData.phone"
          :placeholder="t('user.phonePlaceholder')"
          maxlength="11"
          show-word-limit
        />
      </el-form-item>

      <el-form-item :label="t('common.status')" prop="status">
        <el-radio-group v-model="formData.status">
          <el-radio :value="1">{{ t('common.enable') }}</el-radio>
          <el-radio :value="0">{{ t('common.disable') }}</el-radio>
        </el-radio-group>
      </el-form-item>

      <el-form-item :label="t('user.roleIds')" prop="role_ids">
        <el-select
          v-model="formData.role_ids"
          multiple
          filterable
          :placeholder="t('user.rolePlaceholder')"
          style="width: 100%"
          :loading="rolesLoading"
        >
          <el-option
            v-for="role in roleOptions"
            :key="role.hash_id"
            :label="role.name"
            :value="role.hash_id"
          />
        </el-select>
      </el-form-item>

      <el-form-item :label="t('user.avatar')" prop="avatar">
        <div class="avatar-container">
          <img v-if="formData.avatar" :src="handleImageUrl(formData.avatar)" class="avatar" />
          <div v-else class="avatar-uploader-icon">
            <el-icon><Plus /></el-icon>
          </div>
          <ImageCropper v-model="formData.avatar" @crop="handleAvatarCrop" />
        </div>
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
import { ref, reactive, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import type { User, Role } from '@/types/api'
import { createUser, updateUser, getUserRoles } from '@/api/user'
import ImageCropper from '@/components/ImageCropper/index.vue'
import { handleImageUrl } from '@/utils/imageUrl'

const { t } = useI18n()

const props = defineProps<{
  visible: boolean
  editData: User | null
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  submit: []
}>()

const formRef = ref<FormInstance>()
const submitLoading = ref(false)
const roleOptions = ref<Role[]>([])
const rolesLoading = ref(false)

const getDefaultForm = () => ({
  username: '',
  password: '',
  password_confirmation: '',
  real_name: '',
  email: '',
  phone: '',
  status: 1,
  role_ids: [] as string[],
  avatar: '',
})

const formData = reactive(getDefaultForm())

// Password confirmation validator
const validatePasswordConfirm = (_rule: any, value: string, callback: any) => {
  if (!formData.password) {
    callback()
  } else if (!value) {
    callback(new Error(t('user.confirmPasswordPlaceholder')))
  } else if (value !== formData.password) {
    callback(new Error(t('user.passwordMismatch')))
  } else {
    callback()
  }
}

const computedRules = computed<FormRules>(() => {
  const rules: FormRules = {
    username: [
      { required: true, message: t('user.usernameRequired'), trigger: 'blur' },
      { min: 3, max: 20, message: t('user.usernameLength'), trigger: 'blur' },
      { pattern: /^[a-zA-Z0-9_]+$/, message: t('user.usernamePattern'), trigger: 'blur' },
    ],
    password: [
      { required: !props.editData, message: t('user.passwordRequired'), trigger: 'blur' },
      { min: 6, max: 20, message: t('user.passwordMin'), trigger: 'blur', required: false },
    ],
    password_confirmation: [
      { validator: validatePasswordConfirm, trigger: 'blur' },
    ],
    email: [
      { required: true, message: t('user.emailRequired'), trigger: 'blur' },
      { type: 'email', message: t('user.emailInvalid'), trigger: 'blur' },
    ],
    phone: [
      { pattern: /^1[3-9]\d{9}$/, message: t('user.phoneInvalid'), trigger: 'blur' },
    ],
    status: [
      { required: true, message: t('user.statusRequired'), trigger: 'change' },
    ],
    role_ids: [
      { required: true, message: t('user.roleRequired'), trigger: 'change' },
    ],
  }

  return rules
})

async function fetchRoles() {
  rolesLoading.value = true
  try {
    const data = await getUserRoles()
    roleOptions.value = data as Role[]
  } catch (error: any) {
    ElMessage.error(error?.message || t('common.operationFailed'))
  } finally {
    rolesLoading.value = false
  }
}

watch(
  () => props.visible,
  (val) => {
    if (val) {
      fetchRoles()
      Object.assign(formData, getDefaultForm())
      formRef.value?.clearValidate()
      if (props.editData) {
        Object.assign(formData, {
          username: props.editData.username || '',
          password: '',
          password_confirmation: '',
          real_name: props.editData.real_name || '',
          email: props.editData.email || '',
          phone: props.editData.phone || '',
          status: props.editData.status ?? 1,
          role_ids: props.editData.roles?.map((r) => r.hash_id) || [],
          avatar: props.editData.avatar || '',
        })
      }
    }
  }
)

function handleClosed() {
  Object.assign(formData, getDefaultForm())
  formRef.value?.resetFields()
}

function handleAvatarCrop(croppedImage: string) {
  formData.avatar = croppedImage
  ElMessage.success(t('profile.avatarUploadSuccess'))
}

async function handleSubmit() {
  if (!formRef.value) return
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  submitLoading.value = true
  try {
    const submitData: any = {
      username: formData.username,
      real_name: formData.real_name,
      email: formData.email,
      phone: formData.phone,
      status: formData.status,
      role_ids: formData.role_ids,
      avatar: formData.avatar,
    }

    // Include password fields only when provided
    if (!props.editData) {
      submitData.password = formData.password
      submitData.password_confirmation = formData.password_confirmation
    } else if (formData.password) {
      submitData.password = formData.password
      submitData.password_confirmation = formData.password_confirmation
    }

    if (props.editData) {
      await updateUser(props.editData.hash_id, submitData)
      ElMessage.success(t('common.updateSuccess'))
    } else {
      await createUser(submitData)
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

<style lang="scss" scoped>
.avatar-container {
  display: flex;
  align-items: center;
  gap: 20px;

  .avatar {
    width: 100px;
    height: 100px;
    display: block;
    object-fit: cover;
    border-radius: 4px;
  }

  .avatar-uploader-icon {
    font-size: 28px;
    color: #8c939d;
    width: 100px;
    height: 100px;
    text-align: center;
    line-height: 100px;
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
  }
}
</style>
