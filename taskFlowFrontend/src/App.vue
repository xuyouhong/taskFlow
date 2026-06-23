<template>
  <el-config-provider :locale="elLocale">
    <router-view />
  </el-config-provider>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'
import zhCn from 'element-plus/es/locale/lang/zh-cn'
import en from 'element-plus/es/locale/lang/en'

const appStore = useAppStore()
const authStore = useAuthStore()

const elLocale = computed(() => (appStore.language === 'zh-CN' ? zhCn : en))

onMounted(() => {
  appStore.initTheme()
  authStore.initAuth()
})
</script>

<style>
#app {
  width: 100%;
  height: 100%;
  overflow: hidden;
}
</style>
