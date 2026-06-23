<template>
  <div class="navbar-action" @click="toggle" :title="isFullscreen ? t('layout.exitFullscreen') : t('layout.fullscreen')">
    <el-icon :size="18">
      <FullScreen v-if="!isFullscreen" />
      <Aim v-else />
    </el-icon>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { FullScreen, Aim } from '@element-plus/icons-vue'
import screenfull from 'screenfull'
import { ElMessage } from 'element-plus'

const { t } = useI18n()
const isFullscreen = ref(false)

function toggle() {
  if (!screenfull.isEnabled) {
    ElMessage.warning('Fullscreen not supported')
    return
  }
  screenfull.toggle()
  isFullscreen.value = !isFullscreen.value
}

if (screenfull.isEnabled) {
  screenfull.on('change', () => {
    isFullscreen.value = screenfull.isFullscreen
  })
}
</script>
