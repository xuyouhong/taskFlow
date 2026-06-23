<template>
  <el-dropdown trigger="click" @command="handleCommand">
    <div class="navbar-action">
      <el-icon :size="18"><SetUp /></el-icon>
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
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { SetUp } from '@element-plus/icons-vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const { locale } = useI18n()

function handleCommand(lang: string) {
  appStore.setLanguage(lang as 'zh-CN' | 'en')
  locale.value = lang
}
</script>
