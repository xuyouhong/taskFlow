<template>
  <div class="pagination-container">
    <el-pagination
      :current-page="pagination.current_page"
      :page-size="pagination.per_page"
      :page-sizes="[10, 15, 20, 50, 100]"
      :total="pagination.total"
      layout="total, sizes, prev, pager, next, jumper"
      @size-change="handleSizeChange"
      @current-change="handleCurrentChange"
    />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  pagination: {
    current_page: number
    per_page: number
    total: number
  }
}>()

const emit = defineEmits<{
  sizeChange: [size: number]
  currentChange: [page: number]
}>()

function handleSizeChange(size: number) {
  emit('sizeChange', size)
}

function handleCurrentChange(page: number) {
  emit('currentChange', page)
}
</script>

<style scoped>
.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}
</style>
