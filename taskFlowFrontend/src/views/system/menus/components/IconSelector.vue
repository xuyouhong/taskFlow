<template>
  <el-dialog
    :model-value="visible"
    :title="t('menu.selectIcon')"
    width="720px"
    draggable
    :close-on-click-modal="false"
    :destroy-on-close="true"
    @update:model-value="$emit('update:visible', $event)"
  >
    <div class="icon-selector">
      <!-- Search -->
      <el-input
        v-model="searchText"
        :placeholder="t('layout.search')"
        clearable
        class="icon-search"
        :prefix-icon="Search"
      />

      <!-- Icon Grid -->
      <div class="icon-grid">
        <div
          v-for="name in filteredIcons"
          :key="name"
          class="icon-item"
          :class="{ active: selectedIcon === name }"
          @click="handleSelect(name)"
        >
          <el-icon :size="24">
            <component :is="name" />
          </el-icon>
          <span class="icon-name">{{ name }}</span>
        </div>
      </div>

      <!-- Empty state -->
      <el-empty
        v-if="filteredIcons.length === 0"
        :description="t('common.noData')"
        :image-size="80"
      />
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import * as ElementPlusIcons from '@element-plus/icons-vue'

const { t } = useI18n()

defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  'update:visible': [value: boolean]
  select: [name: string]
}>()

const searchText = ref('')
const selectedIcon = ref('')

const allIconNames = Object.keys(ElementPlusIcons)

const filteredIcons = computed(() => {
  if (!searchText.value) return allIconNames
  const keyword = searchText.value.toLowerCase()
  return allIconNames.filter((name) => name.toLowerCase().includes(keyword))
})

function handleSelect(name: string) {
  selectedIcon.value = name
  emit('select', name)
  emit('update:visible', false)
}
</script>

<style scoped>
.icon-selector {
  max-height: 500px;
  display: flex;
  flex-direction: column;
}
.icon-search {
  margin-bottom: 16px;
  flex-shrink: 0;
}
.icon-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 8px;
  overflow-y: auto;
  max-height: 420px;
  padding: 4px;
}
.icon-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px 4px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}
.icon-item:hover {
  background-color: var(--el-color-primary-light-9, #ecf5ff);
  border-color: var(--el-color-primary-light-5, #a0cfff);
}
.icon-item.active {
  background-color: var(--el-color-primary-light-8, #d9ecff);
  border-color: var(--el-color-primary, #409eff);
}
.icon-name {
  margin-top: 4px;
  font-size: 11px;
  color: var(--el-text-color-secondary, #909399);
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
}
</style>
