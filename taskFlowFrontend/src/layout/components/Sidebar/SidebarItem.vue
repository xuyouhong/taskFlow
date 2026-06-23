<template>
  <div v-if="!isHidden">
    <!-- Has visible children: render as sub-menu -->
    <template v-if="hasVisibleChildren">
      <el-sub-menu :index="basePath">
        <template #title>
          <el-icon v-if="item.icon">
            <component :is="getIcon(item.icon)" />
          </el-icon>
          <span class="menu-title">{{ translatedTitle }}</span>
        </template>
        <SidebarItem
          v-for="child in visibleChildren"
          :key="child.hash_id"
          :item="child"
          :base-path="resolveChildPath(child.path)"
        />
      </el-sub-menu>
    </template>
    <!-- No children or single child: render as menu item -->
    <template v-else>
      <el-menu-item :index="fullPath">
        <el-icon v-if="displayItem.icon">
          <component :is="getIcon(displayItem.icon)" />
        </el-icon>
        <template #title>{{ displayTitle }}</template>
      </el-menu-item>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { Menu } from '@/types/api'
import * as ElementPlusIcons from '@element-plus/icons-vue'
import { menuNameMap, iconMap } from '@/constants/menu'

const props = defineProps<{
  item: Menu
  basePath: string
}>()

const { t } = useI18n()

const visibleChildren = computed(() => {
  return (props.item.children || []).filter(
    (child) => child.status === 1 && child.type !== 3
  )
})

const hasVisibleChildren = computed(() => {
  return visibleChildren.value.length >= 1
})

const isHidden = computed(() => {
  return props.item.status === 0 || props.item.type === 3
})

const displayItem = computed(() => {
  if (visibleChildren.value.length === 1) {
    return visibleChildren.value[0]
  }
  return props.item
})

const fullPath = computed(() => {
  if (visibleChildren.value.length === 1) {
    return resolveChildPath(visibleChildren.value[0].path)
  }
  // Ensure leading slash
  const p = props.basePath
  return p.startsWith('/') ? p : '/' + p
})

const translatedTitle = computed(() => {
  return translateMenuName(props.item.name)
})

const displayTitle = computed(() => {
  return translateMenuName(displayItem.value.name)
})

function translateMenuName(name: string): string {
  const key = menuNameMap[name]
  if (key) return t(key)
  return name
}

function getIcon(iconName: string): any {
  if (!iconName) return null
  // Check icon map first
  const mapped = iconMap[iconName]
  if (mapped) {
    return (ElementPlusIcons as any)[mapped]
  }
  // Try PascalCase conversion
  const pascalName = iconName
    .replace(/^el-icon-/, '')
    .split('-')
    .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
    .join('')
  return (ElementPlusIcons as any)[pascalName] || (ElementPlusIcons as any)[iconName] || null
}

function resolveChildPath(childPath: string): string {
  if (childPath.startsWith('/')) return childPath
  const base = props.basePath.endsWith('/') ? props.basePath : props.basePath + '/'
  return (base + childPath).replace(/\/+/g, '/')
}
</script>
