<template>
  <div class="wang-editor-container">
    <div ref="toolbarRef" class="toolbar-container"></div>
    <div
      ref="editorRef"
      class="editor-content"
      :style="{ minHeight: (minHeight || 300) + 'px', maxHeight: (maxHeight || 500) + 'px' }"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, toRefs } from 'vue'
import { createEditor, createToolbar } from '@wangeditor/editor'
import '@wangeditor/editor/dist/css/style.css'
import { uploadFile } from '@/api/upload'
import { ElMessage } from 'element-plus'

const props = defineProps<{
  modelValue?: string
  readOnly?: boolean
  placeholder?: string
  minHeight?: number
  maxHeight?: number
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'change', value: string): void
  (e: 'ready', editor: any): void
}>()

const { modelValue, readOnly, placeholder, minHeight, maxHeight } = toRefs(props)

const editorRef = ref<HTMLElement | null>(null)
const toolbarRef = ref<HTMLElement | null>(null)
let editor: any = null
let toolbar: any = null

const initEditor = () => {
  if (!editorRef.value || !toolbarRef.value) return

  editor = createEditor({
    selector: editorRef.value,
    html: modelValue.value || '',
    config: {
      placeholder: placeholder.value || '请输入内容',
      readOnly: readOnly.value || false,
      autoFocus: false,
      MENU_CONF: {
        uploadImage: {
          customUpload: async (file: File, insertFn: Function) => {
            try {
              const res = await uploadFile(file)
              if (res.url) {
                insertFn(res.url, file.name, res.url)
              } else {
                ElMessage.error('图片上传失败: 响应中没有url字段')
              }
            } catch (error) {
              console.error('图片上传失败:', error)
              const errorMessage = error instanceof Error ? error.message : '未知错误'
              ElMessage.error(`图片上传失败: ${errorMessage}`)
            }
          },
        },
      },
    },
  })

  toolbar = createToolbar({
    editor,
    selector: toolbarRef.value,
    config: {
      excludeKeys: ['group-video', 'insertVideo', 'group-code', 'codeBlock', 'code'],
    },
  })

  editor.on('change', () => {
    const content = editor.getHtml()
    emit('update:modelValue', content)
    emit('change', content)
  })

  emit('ready', editor)
}

const destroyEditor = () => {
  if (editor) {
    editor.destroy()
    editor = null
  }
  if (toolbar) {
    toolbar.destroy()
    toolbar = null
  }
}

watch(modelValue, (newValue) => {
  if (editor && newValue !== editor.getHtml()) {
    editor.setHtml(newValue || '')
  }
})

watch(readOnly, (newValue) => {
  if (editor) {
    editor.setConfig({
      readOnly: newValue || false,
    })
  }
})

onMounted(() => {
  initEditor()
})

onUnmounted(() => {
  destroyEditor()
})

const getEditor = () => editor
const getContent = () => editor?.getHtml() || ''
const setContent = (html: string) => editor?.setHtml(html)

defineExpose({
  getEditor,
  getContent,
  setContent,
})
</script>

<style lang="scss" scoped>
.wang-editor-container {
  width: 100%;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;
  box-sizing: border-box;

  .toolbar-container {
    border-bottom: 1px solid #dcdfe6;
    background-color: #f5f7fa;
  }

  .editor-content {
    min-height: 300px;
    max-height: 500px;
    overflow-y: auto;
  }

  :deep(.w-e-toolbar) {
    border-bottom: 1px solid #dcdfe6;
    background-color: #f5f7fa;
  }

  :deep(.w-e-text-container) {
    overflow-y: auto;
  }
}
</style>
