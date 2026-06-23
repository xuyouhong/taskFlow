<template>
  <el-dropdown trigger="click" @command="handleCommand">
    <div class="navbar-action user-dropdown">
      <el-avatar :src="avatarUrl" :size="28" class="avatar-bordered">
        {{ authStore.userInfo?.real_name?.charAt(0)?.toUpperCase() || 'U' }}
      </el-avatar>
      <span class="username">{{ authStore.userInfo?.real_name || authStore.userInfo?.username || '' }}</span>
      <el-icon><ArrowDown /></el-icon>
    </div>
    <template #dropdown>
      <el-dropdown-menu>
        <el-dropdown-item command="profile">
          <el-icon><User /></el-icon>
          {{ t('layout.profile') }}
        </el-dropdown-item>
        <el-dropdown-item command="password">
          <el-icon><Lock /></el-icon>
          {{ t('layout.password') }}
        </el-dropdown-item>
        <el-dropdown-item divided command="logout">
          <el-icon><SwitchButton /></el-icon>
          {{ t('layout.logout') }}
        </el-dropdown-item>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { User, Lock, SwitchButton, ArrowDown } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { useAuthStore } from '@/stores/auth'
import { usePermissionStore } from '@/stores/permission'

const router = useRouter()
const { t } = useI18n()
const authStore = useAuthStore()
const permissionStore = usePermissionStore()

const avatarUrl = computed(() => {
  const avatar = authStore.userInfo?.avatar
  if (!avatar) return ''
  if (avatar.startsWith('http')) return avatar
  return import.meta.env.VITE_UPLOAD_URL + avatar
})

function handleCommand(command: string) {
  switch (command) {
    case 'profile':
      router.push('/profile')
      break
    case 'password':
      router.push('/password')
      break
    case 'logout':
      ElMessageBox.confirm(t('layout.confirmLogout'), t('common.tip'), {
        confirmButtonText: t('common.confirm'),
        cancelButtonText: t('common.cancel'),
        type: 'warning',
      }).then(async () => {
        await authStore.logout()
        permissionStore.clearRoutes(router)
        router.push('/login')
      }).catch(() => {})
      break
  }
}
</script>

<style scoped>
.user-dropdown {
  display: flex;
  align-items: center;
  gap: 8px;
}
.avatar-bordered {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: 2px solid var(--el-color-primary-light-5);
  transition: border-color 0.3s;

  &:hover {
    border-color: var(--el-color-primary);
  }
}
.username {
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 14px;
}
</style>
