import { ref, reactive, type Ref } from 'vue'
import type { PaginationResponse } from '@/types/api'
import { ElMessage } from 'element-plus'

interface UseTableOptions<T> {
  fetchApi: (params: any) => Promise<any>
  defaultSearch?: Record<string, any>
  immediate?: boolean
}

export function useTable<T>({
  fetchApi,
  defaultSearch = {},
  immediate = true,
}: UseTableOptions<T>) {
  const tableData = ref<T[]>([]) as Ref<T[]>
  const loading = ref(false)
  const selectedRows = ref<T[]>([]) as Ref<T[]>
  const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0,
    last_page: 1,
  })
  const searchForm = reactive({ ...defaultSearch })

  async function loadData() {
    loading.value = true
    try {
      const params = {
        page: pagination.current_page,
        per_page: pagination.per_page,
        ...searchForm,
      }
      // Remove empty params
      Object.keys(params).forEach((key) => {
        if (params[key] === '' || params[key] === null || params[key] === undefined) {
          delete params[key]
        }
      })

      const res = await fetchApi(params)
      const data = res as any
      // Support Laravel paginate format: { data: [...], total, current_page, per_page, last_page }
      if (data?.data && Array.isArray(data.data)) {
        tableData.value = data.data
        pagination.total = data.total || 0
        pagination.current_page = data.current_page || 1
        pagination.per_page = data.per_page || 15
        pagination.last_page = data.last_page || 1
      } else if (data?.list) {
        tableData.value = data.list
        pagination.total = data.total || 0
        pagination.current_page = data.current_page || 1
        pagination.per_page = data.per_page || 15
        pagination.last_page = data.last_page || 1
      } else if (Array.isArray(data)) {
        tableData.value = data
        pagination.total = data.length
      }
    } catch (error: any) {
      ElMessage.error(error?.message || '加载数据失败')
    } finally {
      loading.value = false
    }
  }

  function handleSearch() {
    pagination.current_page = 1
    loadData()
  }

  function handleReset() {
    Object.keys(searchForm).forEach((key) => {
      (searchForm as any)[key] = (defaultSearch as any)[key] ?? ''
    })
    pagination.current_page = 1
    loadData()
  }

  function handleSizeChange(size: number) {
    pagination.per_page = size
    pagination.current_page = 1
    loadData()
  }

  function handleCurrentChange(page: number) {
    pagination.current_page = page
    loadData()
  }

  function handleSelectionChange(rows: T[]) {
    selectedRows.value = rows
  }

  if (immediate) {
    loadData()
  }

  return {
    tableData,
    loading,
    selectedRows,
    pagination,
    searchForm,
    loadData,
    handleSearch,
    handleReset,
    handleSizeChange,
    handleCurrentChange,
    handleSelectionChange,
  }
}
