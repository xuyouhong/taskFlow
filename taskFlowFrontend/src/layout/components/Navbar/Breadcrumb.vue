<template>
  <el-breadcrumb separator="/" class="breadcrumb-container">
    <transition-group name="breadcrumb">
      <el-breadcrumb-item v-for="(item, index) in breadcrumbs" :key="item.path">
        <span
          v-if="index === breadcrumbs.length - 1"
          class="no-redirect"
        >{{ item.title }}</span>
        <router-link v-else :to="item.path">{{ item.title }}</router-link>
      </el-breadcrumb-item>
    </transition-group>
  </el-breadcrumb>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { menuNameMap } from '@/constants/menu'

const route = useRoute()
const { t } = useI18n()

interface BreadcrumbItem {
  title: string
  path: string
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => {
  const matched = route.matched.filter((item) => item.meta?.title)
  const result: BreadcrumbItem[] = []

  for (const item of matched) {
    if (item.meta?.title) {
      const rawTitle = item.meta.title as string
      let title: string

      if (menuNameMap[rawTitle]) {
        // Known mapping — translate via mapped key
        title = t(menuNameMap[rawTitle])
      } else if (rawTitle.includes('.')) {
        // Might be an i18n key (e.g., 'user.userList') — try translating directly
        const translated = t(rawTitle)
        title = translated !== rawTitle ? translated : rawTitle
      } else {
        title = rawTitle
      }

      result.push({
        title,
        path: item.path || '/',
      })
    }
  }

  return result
})
</script>

<style scoped>
.no-redirect {
  color: var(--text-secondary-color);
  cursor: text;
}
</style>
