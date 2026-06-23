<template>
  <div class="hamburger-container" @click="toggleSidebar">
    <el-icon :size="20">
      <Fold v-if="!appStore.sidebarCollapsed" />
      <Expand v-else />
    </el-icon>
  </div>
</template>

<script setup lang="ts">
import { Fold, Expand } from '@element-plus/icons-vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

function toggleSidebar() {
  appStore.toggleSidebar()
}
</script>
