<template>
  <div class="navbar-action search-panel-wrapper" ref="searchRef">
    <el-icon :size="18" @click="showSearch = !showSearch"><Search /></el-icon>
    <div v-show="showSearch" class="search-panel">
      <el-input
        ref="searchInputRef"
        v-model="searchQuery"
        :placeholder="t('layout.search')"
        clearable
        :prefix-icon="Search"
        @keyup.esc="showSearch = false"
        @keyup.enter="handleSearch"
      />
      <div class="search-results" v-if="filteredMenus.length > 0">
        <div
          v-for="menu in filteredMenus"
          :key="menu.hash_id"
          class="search-item"
          @click="navigateTo(menu)"
        >
          <el-icon><Document /></el-icon>
          <span class="search-item-name">{{ translateMenuName(menu.name) }}</span>
          <span class="search-item-path">{{ menu.path }}</span>
        </div>
      </div>
      <div v-else-if="searchQuery" class="search-empty">
        {{ t('common.noData') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Search, Document } from '@element-plus/icons-vue'
import { usePermissionStore } from '@/stores/permission'
import { menuNameMap } from '@/constants/menu'
import type { Menu } from '@/types/api'

const router = useRouter()
const { t } = useI18n()
const permissionStore = usePermissionStore()

const showSearch = ref(false)
const searchQuery = ref('')
const searchRef = ref<HTMLElement>()
const searchInputRef = ref<any>()

function translateMenuName(name: string): string {
  const key = menuNameMap[name]
  return key ? t(key) : name
}

const filteredMenus = computed(() => {
  if (!searchQuery.value) return []
  const query = searchQuery.value.toLowerCase()
  const result: Menu[] = []

  function search(menus: Menu[]) {
    for (const menu of menus) {
      if (menu.type === 2 && menu.status === 1) {
        const name = translateMenuName(menu.name).toLowerCase()
        const path = (menu.path || '').toLowerCase()
        if (name.includes(query) || path.includes(query)) {
          result.push(menu)
        }
      }
      if (menu.children) search(menu.children)
    }
  }
  search(permissionStore.menuList)
  return result.slice(0, 10)
})

function navigateTo(menu: Menu) {
  router.push(menu.path)
  showSearch.value = false
  searchQuery.value = ''
}

function handleSearch() {
  if (filteredMenus.value.length > 0) {
    navigateTo(filteredMenus.value[0])
  }
}

function handleClickOutside(e: MouseEvent) {
  if (searchRef.value && !searchRef.value.contains(e.target as Node)) {
    showSearch.value = false
  }
}

watch(showSearch, (val) => {
  if (val) {
    nextTick(() => searchInputRef.value?.focus())
  }
})

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault()
      showSearch.value = !showSearch.value
    }
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.search-panel-wrapper {
  position: relative;
}
.search-panel {
  position: absolute;
  top: 100%;
  right: 0;
  width: 320px;
  background: var(--bg-color);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  box-shadow: var(--box-shadow);
  padding: 12px;
  z-index: 2000;
  margin-top: 4px;
}
.search-results {
  max-height: 300px;
  overflow-y: auto;
  margin-top: 8px;
}
.search-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 4px;
  transition: background 0.2s;
}
.search-item:hover {
  background: var(--fill-color);
}
.search-item-name {
  flex: 1;
  color: var(--text-primary-color);
}
.search-item-path {
  font-size: 12px;
  color: var(--text-secondary-color);
}
.search-empty {
  text-align: center;
  padding: 16px;
  color: var(--text-secondary-color);
}
</style>
