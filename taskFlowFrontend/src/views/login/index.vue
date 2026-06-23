<template>
  <div class="login-container">
    <div class="login-card">
      <div class="language-switch">
        <el-dropdown trigger="click" @command="handleLanguageChange">
          <div class="language-trigger">
            <el-icon :size="16"><SetUp /></el-icon>
            <span>{{ appStore.language === 'zh-CN' ? '中文' : 'English' }}</span>
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item command="zh-CN" :class="{ 'is-active': appStore.language === 'zh-CN' }">
                中文
              </el-dropdown-item>
              <el-dropdown-item command="en" :class="{ 'is-active': appStore.language === 'en' }">
                English
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
      <h2 class="login-title">{{ t('login.title') }}</h2>
      <el-form ref="loginFormRef" :model="loginForm" :rules="loginRules" @keyup.enter="handleLogin">
        <el-form-item prop="username">
          <el-input
            v-model="loginForm.username"
            :placeholder="t('login.usernamePlaceholder')"
            :prefix-icon="User"
            size="large"
          />
        </el-form-item>
        <el-form-item prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            :placeholder="t('login.passwordPlaceholder')"
            :prefix-icon="Lock"
            size="large"
            show-password
          />
        </el-form-item>
        <el-form-item prop="captcha_code">
          <div class="captcha-row">
            <el-input
              v-model="loginForm.captcha_code"
              :placeholder="t('login.captchaPlaceholder')"
              :prefix-icon="Key"
              size="large"
              class="captcha-input"
            />
            <img
              :src="captchaImage"
              class="captcha-img"
              @click="refreshCaptcha"
              :title="t('common.refresh')"
            />
          </div>
        </el-form-item>
        <el-form-item>
          <el-checkbox v-model="rememberUsername">{{ t('login.rememberUsername') }}</el-checkbox>
        </el-form-item>
        <el-form-item>
          <el-button
            type="primary"
            size="large"
            :loading="loading"
            class="login-btn"
            @click="handleLogin"
          >
            {{ t('login.loginButton') }}
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { User, Lock, Key, SetUp } from '@element-plus/icons-vue'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { getCaptcha } from '@/api/auth'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const route = useRoute()
const { t, locale } = useI18n()
const authStore = useAuthStore()
const appStore = useAppStore()

const loginFormRef = ref<FormInstance>()
const loading = ref(false)
const captchaImage = ref('')
const captchaKey = ref('')
const rememberUsername = ref(false)

const loginForm = reactive({
  username: '',
  password: '',
  captcha_key: '',
  captcha_code: '',
})

const loginRules: FormRules = {
  username: [{ required: true, message: () => t('login.usernameRequired'), trigger: 'blur' }],
  password: [
    { required: true, message: () => t('login.passwordRequired'), trigger: 'blur' },
    { min: 6, message: () => t('login.passwordMinLength'), trigger: 'blur' },
  ],
  captcha_code: [{ required: true, message: () => t('login.captchaRequired'), trigger: 'blur' }],
}

function handleLanguageChange(lang: string) {
  appStore.setLanguage(lang as 'zh-CN' | 'en')
  locale.value = lang
  ElMessage.success(t(`language.switchedTo${lang === 'zh-CN' ? 'Chinese' : 'English'}`))
}

async function refreshCaptcha() {
  try {
    const res = await getCaptcha() as any
    captchaImage.value = res.captcha_image || ''
    captchaKey.value = res.captcha_key || ''
    loginForm.captcha_key = captchaKey.value
  } catch {
    ElMessage.error(t('login.captchaFetchFailed'))
  }
}

async function handleLogin() {
  if (!loginFormRef.value) return
  try {
    await loginFormRef.value.validate()
  } catch {
    return
  }

  loading.value = true
  loginForm.captcha_key = captchaKey.value
  try {
    await authStore.login(loginForm)
    ElMessage.success(t('login.loginSuccess'))

    if (rememberUsername.value) {
      localStorage.setItem('remember_username', loginForm.username)
    } else {
      localStorage.removeItem('remember_username')
    }

    const redirect = (route.query.redirect as string) || '/dashboard'
    router.push(redirect)
  } catch (error: any) {
    const msg = error?.response?.data?.message || error?.message || t('login.loginFailed')
    ElMessage.error(msg)
    refreshCaptcha()
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  refreshCaptcha()
  const saved = localStorage.getItem('remember_username')
  if (saved) {
    loginForm.username = saved
    rememberUsername.value = true
  }
})
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.login-card {
  width: 420px;
  padding: 40px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  position: relative;
}
.language-switch {
  position: absolute;
  top: 16px;
  right: 20px;
}
.language-trigger {
  display: flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  color: #606266;
  font-size: 14px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background-color 0.2s;
}
.language-trigger:hover {
  background-color: #f5f7fa;
  color: #409eff;
}
.login-title {
  text-align: center;
  margin-bottom: 30px;
  font-size: 24px;
  color: #303133;
}
.captcha-row {
  display: flex;
  width: 100%;
  gap: 12px;
}
.captcha-input {
  flex: 1;
}
.captcha-img {
  height: 40px;
  cursor: pointer;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
}
.login-btn {
  width: 100%;
}
</style>
