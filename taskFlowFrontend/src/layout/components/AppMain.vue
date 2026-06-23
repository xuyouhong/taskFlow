<template>
  <section class="app-main">
    <router-view v-slot="{ Component }">
      <transition name="fade-transform" mode="out-in">
        <keep-alive :include="tagsViewStore.cachedViews">
          <component
            :is="Component"
            :key="route.path + '_' + tagsViewStore.refreshKey"
            class="page-wrapper"
          />
        </keep-alive>
      </transition>
    </router-view>
  </section>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { useTagsViewStore } from '@/stores/tagsView'

const route = useRoute()
const tagsViewStore = useTagsViewStore()
</script>
