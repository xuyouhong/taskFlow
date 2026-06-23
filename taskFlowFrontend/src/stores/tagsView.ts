import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { RouteLocationNormalized } from 'vue-router'

export interface TagView {
  path: string
  name: string | symbol | null | undefined
  title: string
  fullPath: string
  meta?: any
  affix?: boolean
}

export const useTagsViewStore = defineStore('tagsView', () => {
  const visitedViews = ref<TagView[]>([])
  const cachedViews = ref<string[]>([])
  const refreshKey = ref(0)

  function addTag(route: RouteLocationNormalized) {
    // Don't add if already exists
    if (visitedViews.value.some((v) => v.path === route.path)) return

    const tag: TagView = {
      path: route.path,
      name: route.name,
      title: (route.meta?.title as string) || 'Untitled',
      fullPath: route.fullPath,
      meta: route.meta,
      affix: route.meta?.affix as boolean,
    }

    visitedViews.value.push(tag)

    // Cache the view
    const name = route.name as string
    if (name && route.meta?.keepAlive) {
      if (!cachedViews.value.includes(name)) {
        cachedViews.value.push(name)
      }
    }
  }

  function removeTag(tag: TagView): string | undefined {
    const index = visitedViews.value.findIndex((v) => v.path === tag.path)
    if (index === -1) return undefined

    visitedViews.value.splice(index, 1)

    // Remove from cache
    const name = tag.name as string
    if (name) {
      const cacheIndex = cachedViews.value.indexOf(name)
      if (cacheIndex > -1) {
        cachedViews.value.splice(cacheIndex, 1)
      }
    }

    // Return the path to navigate to
    if (visitedViews.value.length > 0) {
      const nextTag = visitedViews.value[Math.min(index, visitedViews.value.length - 1)]
      return nextTag.fullPath
    }
    return '/dashboard'
  }

  function removeOtherTags(tag: TagView) {
    visitedViews.value = visitedViews.value.filter(
      (v) => v.affix || v.path === tag.path
    )
    cachedViews.value = []
    const name = tag.name as string
    if (name && tag.meta?.keepAlive) {
      cachedViews.value.push(name)
    }
  }

  function removeAllTags() {
    visitedViews.value = visitedViews.value.filter((v) => v.affix)
    cachedViews.value = []
  }

  function removeLeftTags(tag: TagView) {
    const index = visitedViews.value.findIndex((v) => v.path === tag.path)
    if (index > 0) {
      visitedViews.value = visitedViews.value.filter(
        (v, i) => v.affix || i >= index
      )
    }
  }

  function removeRightTags(tag: TagView) {
    const index = visitedViews.value.findIndex((v) => v.path === tag.path)
    if (index > -1) {
      visitedViews.value = visitedViews.value.filter(
        (v, i) => v.affix || i <= index
      )
    }
  }

  function initAffixTags() {
    // Add dashboard as affix tag (only if not already present from sessionStorage restore)
    const dashboardTag: TagView = {
      path: '/dashboard',
      name: 'Dashboard',
      title: 'dashboard.title',
      fullPath: '/dashboard',
      meta: { title: 'dashboard.title', affix: true },
      affix: true,
    }
    if (!visitedViews.value.some((v) => v.path === '/dashboard')) {
      visitedViews.value.unshift(dashboardTag)
    }
  }

  function refreshTag(tag?: TagView) {
    if (tag) {
      // Remove the tag from cache so it re-mounts on next visit
      const name = tag.name as string
      if (name) {
        const cacheIndex = cachedViews.value.indexOf(name)
        if (cacheIndex > -1) {
          cachedViews.value.splice(cacheIndex, 1)
        }
      }
    }
    // Increment key to force current component re-mount
    refreshKey.value++
  }

  return {
    visitedViews,
    cachedViews,
    refreshKey,
    addTag,
    removeTag,
    removeOtherTags,
    removeAllTags,
    removeLeftTags,
    removeRightTags,
    refreshTag,
    initAffixTags,
  }
}, {
  persist: {
    pick: ['visitedViews', 'cachedViews'],
    storage: sessionStorage,
    key: 'tagsView',
  },
})
