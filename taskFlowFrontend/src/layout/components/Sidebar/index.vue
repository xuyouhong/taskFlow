<template>
  <div class="sidebar-container" :class="{ 'is-collapse': appStore.sidebarCollapsed }">
    <div class="sidebar-logo">
      <router-link to="/dashboard">
        <img src="@/assets/logo.png" alt="logo" class="logo-img" :class="{ 'logo-mini': appStore.sidebarCollapsed }" />
        <span class="logo-title" v-show="!appStore.sidebarCollapsed">{{ appTitle }}</span>
      </router-link>
    </div>
    <div class="sidebar-menu-wrap">
      <el-menu
        :default-active="activeMenu"
        :default-openeds="defaultOpeneds"
        :collapse="appStore.sidebarCollapsed"
        :collapse-transition="false"
        :unique-opened="true"
        @select="handleMenuSelect"
      >
        <SidebarItem
          v-for="route in permissionMenus"
          :key="route.hash_id"
          :item="route"
          :base-path="route.path"
        />
      </el-menu>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/stores/app'
import { usePermissionStore } from '@/stores/permission'
import SidebarItem from './SidebarItem.vue'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()
const permissionStore = usePermissionStore()

const appTitle = import.meta.env.VITE_APP_TITLE

const activeMenu = computed(() => {
  return route.meta?.activeMenu || route.path
})

const permissionMenus = computed(() => permissionStore.permissionMenus)

const defaultOpeneds = computed(() => {
  const activePath = activeMenu.value
  const openeds: string[] = []

  function findAncestors(menus: any[], parentBasePath: string): boolean {
    for (const menu of menus) {
      const basePath = resolveBasePath(menu.path, parentBasePath)

      if (menu.children && menu.children.length > 0) {
        // Check if this menu is an ancestor of the active path
        const isAncestor =
          activePath === basePath ||
          (basePath && activePath.startsWith(basePath + '/'))

        if (isAncestor) {
          openeds.push(basePath)
          findAncestors(menu.children, basePath)
        }
      }
    }
    return false
  }

  function resolveBasePath(childPath: string, parentBase: string): string {
    if (childPath.startsWith('/')) return childPath
    const base = parentBase.endsWith('/') ? parentBase : parentBase + '/'
    return (base + childPath).replace(/\/+/g, '/')
  }

  findAncestors(permissionMenus.value, '')
  return openeds
})

function handleMenuSelect(index: string) {
  const targetPath = index.startsWith('/') ? index : '/' + index
  router.push(targetPath)
}
</script>
