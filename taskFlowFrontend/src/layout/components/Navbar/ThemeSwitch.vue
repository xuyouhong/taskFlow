<template>
  <div class="navbar-action" @click="appStore.toggleTheme()" :title="t('layout.theme')">
    <el-icon :size="18">
      <Moon v-if="appStore.theme === 'light'" />
      <Sunny v-else />
    </el-icon>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Moon, Sunny } from '@element-plus/icons-vue'
import { useAppStore } from '@/stores/app'

const { t } = useI18n()
const appStore = useAppStore()
</script>
