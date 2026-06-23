<template>
  <div class="password-container">
    <el-card class="password-card">
      <template #header>
        <div class="card-header">
          <span>{{ t('password.title') }}</span>
        </div>
      </template>

      <el-form ref="formRef" :model="form" :rules="rules" label-width="100px" label-position="left">
        <el-form-item :label="t('password.oldPassword')" prop="old_password">
          <el-input
            v-model="form.old_password"
            type="password"
            :placeholder="t('password.oldPassword')"
            show-password
          />
        </el-form-item>

        <el-form-item :label="t('password.newPassword')" prop="new_password">
          <el-input
            v-model="form.new_password"
            type="password"
            :placeholder="t('password.newPassword')"
            show-password
          />
        </el-form-item>

        <el-form-item :label="t('password.confirmNewPassword')" prop="new_password_confirmation">
          <el-input
            v-model="form.new_password_confirmation"
            type="password"
            :placeholder="t('password.confirmNewPassword')"
            show-password
          />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ t('password.saveChanges') }}
          </el-button>
          <el-button @click="resetForm">{{ t('password.reset') }}</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import type { FormInstance, FormRules } from 'element-plus'
import { changePassword } from '@/api/auth'
import { useI18n } from 'vue-i18n'

// 国际化
const { t } = useI18n()

// 表单引用
const formRef = ref<FormInstance>()

// 表单数据
const form = reactive({
  old_password: '',
  new_password: '',
  new_password_confirmation: '',
})

// 验证规则
const rules: FormRules = {
  old_password: [
    { required: true, message: t('password.oldPasswordRequired'), trigger: 'blur' },
    { min: 6, max: 20, message: t('password.passwordLength'), trigger: 'blur' },
  ],
  new_password: [
    { required: true, message: t('password.newPasswordRequired'), trigger: 'blur' },
    { min: 6, max: 20, message: t('password.passwordLength'), trigger: 'blur' },
  ],
  new_password_confirmation: [
    { required: true, message: t('password.confirmPasswordRequired'), trigger: 'blur' },
    { min: 6, max: 20, message: t('password.passwordLength'), trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== form.new_password) {
          callback(new Error(t('password.passwordMismatch')))
        } else {
          callback()
        }
      },
      trigger: 'blur',
    },
  ],
}

// 状态
const submitting = ref(false)

// 重置表单
const resetForm = () => {
  if (formRef.value) {
    formRef.value.resetFields()
  }
}

// 提交表单
const handleSubmit = async () => {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    submitting.value = true

    await changePassword(form)
    ElMessage.success(t('password.passwordChangedSuccess'))
    resetForm()
  } catch (error: any) {
    if (error.message) {
      ElMessage.error(error.message)
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.password-container {
  padding: 20px;

  .password-card {
    max-width: 600px;
    margin: 0 auto;
  }
}
</style>
