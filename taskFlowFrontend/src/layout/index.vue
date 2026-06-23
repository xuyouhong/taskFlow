<template>
  <div class="app-wrapper" :class="classObj">
    <Sidebar />
    <div class="sidebar-overlay" v-if="isMobile && !appStore.sidebarCollapsed" @click="closeSidebar" />
    <div class="main-container">
      <Navbar />
      <TagsView />
      <AppMain />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useAppStore } from '@/stores/app'
import Sidebar from './components/Sidebar/index.vue'
import Navbar from './components/Navbar/index.vue'
import TagsView from './components/TagsView/index.vue'
import AppMain from './components/AppMain.vue'

const appStore = useAppStore()

const isMobile = ref(window.innerWidth <= 768)

const classObj = computed(() => ({
  hideSidebar: appStore.sidebarCollapsed,
  openSidebar: !appStore.sidebarCollapsed,
  withoutAnimation: false,
}))

function closeSidebar() {
  appStore.toggleSidebar()
}

function handleResize() {
  isMobile.value = window.innerWidth <= 768
  // Auto-collapse sidebar on mobile
  if (isMobile.value && !appStore.sidebarCollapsed) {
    // Don't auto-collapse, let user control it
  }
  // Auto-collapse on small screens when opening
  if (!isMobile.value && appStore.sidebarCollapsed) {
    // Restore sidebar when switching to desktop
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
  appStore.initTheme()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>
