<template>
  <div class="search-container navbar-action" @click="toggleSearchPanel" :title="t('layout.search')">
    <el-icon :size="18">
      <Search />
    </el-icon>

    <div ref="searchPanelRef" class="search-panel" v-if="isVisible" @click.stop>
      <div class="search-input-wrapper">
        <el-input
          v-model="searchKeyword"
          :placeholder="t('layout.search')"
          clearable
          @input="handleSearch"
          @keyup.enter="handleSearch"
          @keyup.esc="closeSearchPanel"
          ref="searchInputRef"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
      </div>
      <div class="search-result-wrapper" v-if="searchResult.length > 0">
        <el-tree
          :data="searchResult"
          :props="treeProps"
          :default-expand-all="true"
          @node-click="handleTreeNodeClick"
          highlight-current
        >
          <template #default="{ data }">
            <div class="tree-node-content">
              <el-icon v-if="data.type === 1"><Folder /></el-icon>
              <el-icon v-else><Document /></el-icon>
              <span>{{ data.title }}</span>
              <span class="tree-node-path">{{ data.path }}</span>
            </div>
          </template>
        </el-tree>
      </div>
      <div class="search-result-empty" v-else-if="searchKeyword">
        <el-empty :description="t('common.noData')" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Search, Folder, Document } from '@element-plus/icons-vue'
import { usePermissionStore } from '@/stores/permission'
import { useI18n } from 'vue-i18n'
import { translateMenuName } from '@/constants/menu'

const props = defineProps<{
  isVisible: boolean
}>()

const emit = defineEmits<{
  'update:isVisible': [visible: boolean]
}>()

const router = useRouter()
const permissionStore = usePermissionStore()
const { t } = useI18n()

const searchKeyword = ref('')
const searchResult = ref<any[]>([])
const searchInputRef = ref<any>(null)
const searchPanelRef = ref<HTMLElement | null>(null)

const treeProps = {
  children: 'children',
  label: 'title',
  isLeaf: (data: any) => data.type === 2,
}

const deepClone = (obj: any) => JSON.parse(JSON.stringify(obj))

const filterMenu = (keyword: string, menus: any[]): any[] => {
  if (!keyword.trim()) return []
  const keywordLower = keyword.toLowerCase()

  const isMatch = (menu: any) => {
    const nameLower = menu.name?.toLowerCase() || ''
    const pathLower = menu.path?.toLowerCase() || ''
    const translatedName = translateMenuName(menu.name, menu.path, t).toLowerCase()
    return (
      nameLower.includes(keywordLower) ||
      translatedName.includes(keywordLower) ||
      pathLower.includes(keywordLower)
    )
  }

  const processMenu = (menuList: any[]): boolean => {
    let hasMatch = false
    const children: any[] = []
    for (const menu of menuList) {
      if (menu.type === 3) continue
      const menuCopy = deepClone(menu)
      menuCopy.title = translateMenuName(menuCopy.name, menuCopy.path, t)

      const menuMatch = isMatch(menuCopy)
      let childrenMatch = false
      if (menuCopy.children?.length > 0) {
        childrenMatch = processMenu(menuCopy.children)
      }
      if (menuMatch || childrenMatch) {
        if (childrenMatch && !menuCopy.children) menuCopy.children = []
        children.push(menuCopy)
        hasMatch = true
      }
    }
    if (hasMatch) {
      menuList.length = 0
      menuList.push(...children)
    }
    return hasMatch
  }

  const menuCopy = deepClone(menus)
  processMenu(menuCopy)
  return menuCopy
}

const handleSearch = () => {
  if (!searchKeyword.value.trim()) {
    searchResult.value = []
    return
  }
  searchResult.value = filterMenu(searchKeyword.value, permissionStore.menuList)
}

const handleTreeNodeClick = (data: any) => {
  if (data.type === 2 && data.path) {
    router.push(data.path)
    closeSearchPanel()
  }
}

const toggleSearchPanel = () => {
  emit('update:isVisible', !props.isVisible)
}

const closeSearchPanel = () => {
  emit('update:isVisible', false)
  searchKeyword.value = ''
  searchResult.value = []
}

watch(
  () => props.isVisible,
  (val) => {
    if (val) {
      nextTick(() => {
        searchInputRef.value?.focus()
      })
    }
  },
)

const handleClickOutside = (event: MouseEvent) => {
  const container = document.querySelector('.search-container')
  if (
    container &&
    props.isVisible &&
    !container.contains(event.target as Node)
  ) {
    closeSearchPanel()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault()
      emit('update:isVisible', !props.isVisible)
    }
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style lang="scss" scoped>
.search-container {
  position: relative;
  z-index: 1001;
}

.search-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 350px;
  background: var(--el-bg-color-overlay);
  border: 1px solid var(--el-border-color-lighter);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  padding: 12px;
  display: flex;
  flex-direction: column;
  max-height: 400px;

  .search-input-wrapper {
    margin-bottom: 12px;

    :deep(.el-input__wrapper) {
      box-shadow: none;
      border-color: var(--el-border-color-lighter);

      &:hover {
        border-color: var(--el-color-primary);
      }
    }

    :deep(.el-input__inner) {
      border-radius: 4px;
    }
  }

  .search-result-wrapper {
    flex: 1;
    overflow-y: auto;
    max-height: 300px;

    :deep(.el-tree) {
      background: transparent;

      :deep(.el-tree-node__content) {
        height: auto;
        padding: 6px 0;

        &:hover {
          background-color: var(--el-fill-color-light);
        }
      }

      :deep(.el-tree-node__expand-icon) {
        font-size: 14px;
      }
    }

    .tree-node-content {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;

      .el-icon {
        font-size: 16px;
        color: var(--el-color-primary);
      }

      span:first-of-type {
        flex: 1;
        color: var(--el-text-color-primary);
      }

      .tree-node-path {
        font-size: 12px;
        color: var(--el-text-color-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 150px;
      }
    }
  }

  .search-result-empty {
    padding: 20px 0;
    text-align: center;
  }
}
</style>
