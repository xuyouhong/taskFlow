import { ref, reactive, watch } from 'vue'
import type { FormInstance, FormRules } from 'element-plus'
import { ElMessage } from 'element-plus'

interface UseFormOptions<T> {
  defaultForm: T
  rules?: FormRules
  submitApi?: (data: T) => Promise<any>
  onSuccess?: () => void
}

export function useForm<T extends Record<string, any>>({
  defaultForm,
  rules,
  submitApi,
  onSuccess,
}: UseFormOptions<T>) {
  const formRef = ref<FormInstance>()
  const form = reactive<T>({ ...defaultForm })
  const submitting = ref(false)
  const isEdit = ref(false)

  function resetForm() {
    Object.keys(defaultForm).forEach((key) => {
      (form as any)[key] = (defaultForm as any)[key]
    })
    formRef.value?.clearValidate()
  }

  function setForm(data: Partial<T>) {
    Object.keys(data).forEach((key) => {
      if (key in form) {
        (form as any)[key] = (data as any)[key]
      }
    })
  }

  async function handleSubmit(): Promise<boolean> {
    if (!formRef.value) return false

    try {
      await formRef.value.validate()
    } catch {
      return false
    }

    if (!submitApi) return true

    submitting.value = true
    try {
      await submitApi({ ...form } as T)
      ElMessage.success(isEdit.value ? '更新成功' : '创建成功')
      resetForm()
      onSuccess?.()
      return true
    } catch (error: any) {
      ElMessage.error(error?.message || '操作失败')
      return false
    } finally {
      submitting.value = false
    }
  }

  return {
    formRef,
    form,
    submitting,
    isEdit,
    rules,
    resetForm,
    setForm,
    handleSubmit,
  }
}
