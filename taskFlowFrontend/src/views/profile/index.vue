<template>
  <div class="profile-container">
    <el-card class="profile-card">
      <template #header>
        <div class="card-header">
          <span>{{ t('profile.title') }}</span>
        </div>
      </template>

      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" label-position="left">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item :label="t('profile.username')" prop="username">
              <el-input v-model="form.username" disabled />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item :label="t('profile.realName')" prop="real_name">
              <el-input v-model="form.real_name" :placeholder="t('profile.realName')" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item :label="t('profile.email')" prop="email">
              <el-input v-model="form.email" :placeholder="t('profile.email')" />
            </el-form-item>
          </el-col>

          <el-col :span="12">
            <el-form-item :label="t('profile.phone')" prop="phone">
              <el-input v-model="form.phone" :placeholder="t('profile.phone')" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item :label="t('profile.avatar')" prop="avatar">
          <div class="avatar-container">
            <div v-if="form.avatar" class="avatar-wrapper">
              <img :src="handleImageUrl(form.avatar)" class="avatar-img" alt="Avatar" />
            </div>
            <el-avatar v-else :size="100" class="avatar">
              {{ form.real_name?.charAt(0)?.toUpperCase() || form.username?.charAt(0)?.toUpperCase() }}
            </el-avatar>
            <image-cropper
              v-model="form.avatar"
              button-type="primary"
              button-size="small"
              @crop="handleAvatarCrop"
            />
          </div>
        </el-form-item>

        <el-form-item :label="t('profile.lastLoginTime')">
          <div class="readonly-field">{{ formatDateTime(form.last_login_at) }}</div>
        </el-form-item>

        <el-form-item :label="t('profile.lastLoginIp')">
          <div class="readonly-field">{{ form.last_login_ip || '未知' }}</div>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ t('profile.saveChanges') }}
          </el-button>
          <el-button @click="resetForm">{{ t('profile.reset') }}</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 角色信息卡片 -->
    <el-card class="roles-card" v-if="userRoles.length > 0">
      <template #header>
        <div class="card-header">
          <span>{{ t('profile.roles') }}</span>
        </div>
      </template>

      <div class="roles-list">
        <el-tag
          v-for="role in userRoles"
          :key="role.hash_id"
          type="primary"
          size="large"
          class="role-tag"
        >
          {{ role.name }}
        </el-tag>
      </div>
    </el-card>

    <!-- 权限信息卡片 -->
    <el-card class="permissions-card" v-if="userPermissions.length > 0">
      <template #header>
        <div class="card-header">
          <span>{{ t('profile.permissions') }}</span>
        </div>
      </template>

      <div class="permissions-list">
        <el-tag
          v-for="permission in userPermissions"
          :key="permission.slug"
          type="info"
          size="small"
          class="permission-tag"
        >
          {{ permission.slug }}
        </el-tag>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { updateProfile } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import { formatDateTime } from '@/utils/format'
import { useI18n } from 'vue-i18n'
import { handleImageUrl } from '@/utils/imageUrl'
import ImageCropper from '@/components/ImageCropper/index.vue'

// 国际化
const { t } = useI18n()

// Store
const authStore = useAuthStore()

// 表单引用
const formRef = ref<FormInstance>()

// 表单数据
const form = reactive({
  username: '',
  real_name: '',
  email: '',
  phone: '',
  avatar: '',
  last_login_at: '',
  last_login_ip: '',
})

// 验证规则
const rules: FormRules = {
  real_name: [
    { required: true, message: t('profile.realName'), trigger: 'blur' },
    { min: 2, max: 20, message: '长度在 2 到 20 个字符', trigger: 'blur' },
  ],
  email: [
    { required: true, message: t('profile.email'), trigger: 'blur' },
    { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' },
  ],
  phone: [{ pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }],
}

// 状态
const submitting = ref(false)

// 计算属性
const userRoles = computed(() => authStore.userInfo?.roles || [])
const userPermissions = computed(() => authStore.userInfo?.permissions || [])

// 加载用户信息
const loadUserInfo = () => {
  const user = authStore.userInfo
  if (user) {
    Object.assign(form, {
      username: user.username || '',
      real_name: user.real_name || '',
      email: user.email || '',
      phone: user.phone || '',
      avatar: user.avatar || '',
      last_login_at: user.last_login_at || '',
      last_login_ip: user.last_login_ip || '',
    })
  }
}

// 处理头像裁剪
const handleAvatarCrop = (croppedImage: string) => {
  form.avatar = croppedImage
  ElMessage.success(t('profile.avatarUploadSuccess'))
}

// 重置表单
const resetForm = () => {
  loadUserInfo()
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    submitting.value = true

    const formData = {
      real_name: form.real_name,
      email: form.email,
      phone: form.phone,
      avatar: form.avatar,
    }

    await updateProfile(formData)
    await authStore.fetchUserInfo()

    ElMessage.success(t('profile.updateSuccess'))
  } catch {
  } finally {
    submitting.value = false
  }
}

// 初始化
onMounted(async () => {
  await authStore.fetchUserInfo()
  loadUserInfo()
})
</script>

<style lang="scss" scoped>
.profile-container {
  padding: 20px;

  .profile-card {
    margin-bottom: 20px;

    .avatar-container {
      display: flex;
      align-items: center;
      gap: 20px;

      .avatar {
        border: 2px solid var(--el-color-primary-light-5);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        transition: border-color 0.3s;

        &:hover {
          border-color: var(--el-color-primary);
        }
      }

      .avatar-wrapper {
        width: 100px;
        height: 100px;
        border: 2px solid var(--el-color-primary-light-5);
        border-radius: 50%;
        overflow: hidden;
        transition: border-color 0.3s;

        &:hover {
          border-color: var(--el-color-primary);
        }

        .avatar-img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
      }
    }

    .readonly-field {
      padding: 8px 12px;
      background-color: var(--el-fill-color-light);
      border-radius: 4px;
      color: var(--el-text-color-regular);
    }
  }

  .roles-card,
  .permissions-card {
    margin-bottom: 20px;

    .roles-list,
    .permissions-list {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .role-tag {
      font-size: 14px;
      padding: 8px 16px;
    }

    .permission-tag {
      font-size: 12px;
    }
  }
}
</style>
