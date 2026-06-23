<template>
  <div class="error-page">
    <div class="error-content">
      <h1 class="error-code">{{ t('error.code403') }}</h1>
      <p class="error-message">{{ t('error.forbidden') }}</p>
      <el-button type="primary" @click="$router.push('/dashboard')">{{ t('error.backHome') }}</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>

<style scoped>
.error-page {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 80vh;
}
.error-content {
  text-align: center;
}
.error-code {
  font-size: 120px;
  font-weight: 700;
  color: var(--primary-color);
  margin: 0;
  line-height: 1;
}
.error-message {
  font-size: 18px;
  color: var(--text-secondary-color);
  margin: 20px 0 30px;
}
</style>
