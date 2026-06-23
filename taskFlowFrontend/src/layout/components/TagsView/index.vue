<template>
  <div class="tags-view-container">
    <div
      v-for="tag in tagsViewStore.visitedViews"
      :key="tag.path"
      class="tags-view-item"
      :class="{ active: isActive(tag) }"
      @click="navigateTo(tag)"
      @contextmenu.prevent="openContextMenu($event, tag)"
    >
      <span>{{ translateTitle(tag.title) }}</span>
      <el-icon
        v-if="!tag.affix"
        class="close-icon"
        @click.stop="closeTag(tag)"
      >
        <Close />
      </el-icon>
    </div>
  </div>

  <!-- Context menu -->
  <ul
    v-show="contextMenu.visible"
    class="tags-view-context-menu"
    :style="{ left: contextMenu.left + 'px', top: contextMenu.top + 'px' }"
    @click.stop
  >
    <li @click="refreshTag">{{ t('tagsView.refresh') }}</li>
    <li v-if="!contextMenu.tag?.affix" @click="closeTag(contextMenu.tag!)">{{ t('tagsView.close') }}</li>
    <li @click="closeOthers">{{ t('tagsView.closeOthers') }}</li>
    <li @click="closeLeft">{{ t('tagsView.closeLeft') }}</li>
    <li @click="closeRight">{{ t('tagsView.closeRight') }}</li>
    <li @click="closeAll">{{ t('tagsView.closeAll') }}</li>
  </ul>
</template>

<script setup lang="ts">
import { reactive, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Close } from '@element-plus/icons-vue'
import { useTagsViewStore, type TagView } from '@/stores/tagsView'
import { menuNameMap } from '@/constants/menu'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const tagsViewStore = useTagsViewStore()

const contextMenu = reactive({
  visible: false,
  left: 0,
  top: 0,
  tag: null as TagView | null,
})

function translateTitle(title: string): string {
  // Check explicit mapping first (Chinese names, static route i18n keys)
  if (menuNameMap[title]) {
    return t(menuNameMap[title])
  }
  // Try as i18n key if it contains a dot (e.g. 'user.title', 'dashboard.title')
  if (title.includes('.')) {
    const translated = t(title)
    if (translated !== title) return translated
  }
  return title
}

function isActive(tag: TagView): boolean {
  return tag.path === route.path
}

function navigateTo(tag: TagView) {
  router.push(tag.fullPath)
}

function closeTag(tag: TagView) {
  const nextPath = tagsViewStore.removeTag(tag)
  if (isActive(tag) && nextPath) {
    router.push(nextPath)
  }
}

function openContextMenu(e: MouseEvent, tag: TagView) {
  contextMenu.left = e.clientX
  contextMenu.top = e.clientY
  contextMenu.tag = tag
  contextMenu.visible = true
}

function closeContextMenu() {
  contextMenu.visible = false
}

function refreshTag() {
  if (contextMenu.tag) {
    tagsViewStore.refreshTag(contextMenu.tag)
  }
  closeContextMenu()
}

function closeOthers() {
  if (contextMenu.tag) {
    tagsViewStore.removeOtherTags(contextMenu.tag)
    router.push(contextMenu.tag.fullPath)
  }
  closeContextMenu()
}

function closeLeft() {
  if (contextMenu.tag) {
    tagsViewStore.removeLeftTags(contextMenu.tag)
    // If the current route was removed, navigate to the context menu tag
    if (!tagsViewStore.visitedViews.some((v) => v.path === route.path)) {
      router.push(contextMenu.tag.fullPath)
    }
  }
  closeContextMenu()
}

function closeRight() {
  if (contextMenu.tag) {
    tagsViewStore.removeRightTags(contextMenu.tag)
    // If the current route was removed, navigate to the context menu tag
    if (!tagsViewStore.visitedViews.some((v) => v.path === route.path)) {
      router.push(contextMenu.tag.fullPath)
    }
  }
  closeContextMenu()
}

function closeAll() {
  tagsViewStore.removeAllTags()
  const affixTags = tagsViewStore.visitedViews.filter((v) => v.affix)
  if (affixTags.length > 0) {
    router.push(affixTags[0].fullPath)
  } else {
    router.push('/dashboard')
  }
  closeContextMenu()
}

watch(route, () => {
  if (route.name && route.meta?.title && !route.meta.hidden) {
    tagsViewStore.addTag(route)
  }
}, { immediate: true })

onMounted(() => {
  tagsViewStore.initAffixTags()
  document.addEventListener('click', closeContextMenu)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', closeContextMenu)
})
</script>
