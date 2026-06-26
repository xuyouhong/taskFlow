<template>
  <div class="navbar">
    <div class="navbar-left">
      <Hamburger />
      <Breadcrumb />
    </div>
    <div class="navbar-right">
      <SearchPanel v-model:is-visible="showSearchPanel" />
      <Fullscreen />
      <LanguageSwitch />
      <ThemeSwitch />
      <NotificationBell />
      <UserDropdown />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Hamburger from './Hamburger.vue'
import Breadcrumb from './Breadcrumb.vue'
import SearchPanel from './SearchPanel.vue'
import Fullscreen from './Fullscreen.vue'
import LanguageSwitch from './LanguageSwitch.vue'
import ThemeSwitch from './ThemeSwitch.vue'
import NotificationBell from './NotificationBell.vue'
import UserDropdown from './UserDropdown.vue'

const showSearchPanel = ref(false)
</script>
