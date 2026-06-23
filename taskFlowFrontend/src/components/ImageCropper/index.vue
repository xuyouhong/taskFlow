<template>
  <el-dialog
    v-model="dialogVisible"
    :title="t('common.imageCropper.title')"
    width="600px"
    draggable
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    :append-to-body="true"
    @click.stop
    @close="handleClose"
  >
    <div class="image-cropper-container">
      <div class="main-content">
        <!-- 裁剪区域 -->
        <div class="crop-area" ref="cropAreaRef">
          <cropper
            ref="cropperRef"
            :src="imageSrc"
            :stencil-props="stencilProps"
            :transitions="false"
            @change="onCropChange"
            @ready="onCropperReady"
            :default-size="defaultSize"
            :default-position="defaultPosition"
            :image-restriction="imageRestriction"
            :stencil-component="stencilComponent"
            class="custom-cropper"
            :resize-image="resizeImageOptions"
            :move-image="moveImageOptions"
            :auto-zoom="false"
            :interaction="interactionOptions"
          />
        </div>

        <!-- 预览区域 -->
        <div class="preview-area" v-if="imageSrc">
          <h4>{{ t('common.imageCropper.preview') }}</h4>
          <div class="preview-container" v-if="cropPreviewUrl">
            <img :src="cropPreviewUrl" class="preview-image" />
          </div>
          <div class="preview-placeholder" v-else>
            {{ t('common.imageCropper.previewPlaceholder') }}
          </div>
        </div>
      </div>

      <!-- 比例选择和控制按钮 -->
      <div class="aspect-ratio-selector" v-if="imageSrc">
        <el-radio-group v-model="selectedRatio" size="small">
          <el-radio-button label="1:1">1:1</el-radio-button>
          <el-radio-button label="4:3">4:3</el-radio-button>
          <el-radio-button label="free">{{ t('common.imageCropper.ratioFree') }}</el-radio-button>
          <el-radio-button label="rotateLeft">{{
            t('common.imageCropper.rotateLeft')
          }}</el-radio-button>
          <el-radio-button label="rotateRight">{{
            t('common.imageCropper.rotateRight')
          }}</el-radio-button>
          <el-radio-button label="reset">{{ t('common.imageCropper.reset') }}</el-radio-button>
        </el-radio-group>
      </div>
    </div>

    <template #footer>
      <span class="dialog-footer">
        <div class="footer-left">
          <el-button @click.stop="reset">{{ t('common.imageCropper.reselect') }}</el-button>
        </div>
        <div class="footer-right">
          <el-button @click.stop="handleClose">{{ t('common.cancel') }}</el-button>
          <el-button type="primary" @click.stop="handleCrop">{{ t('common.confirm') }}</el-button>
        </div>
      </span>
    </template>
  </el-dialog>

  <!-- 触发按钮 -->
  <el-button :type="buttonType" :size="buttonSize" @click="openDialog">
    <slot>
      {{ t('common.imageCropper.upload') }}
    </slot>
  </el-button>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onUnmounted, onMounted } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { useI18n } from 'vue-i18n'

// Constants for consistent sizing
const CONSTANTS = {
  MAX_SQUARE_SIZE: 280,
  MAX_4_3_WIDTH: 320,
  MAX_FREE_WIDTH: 350,
  MAX_LONG_WIDTH: 300,
  MIN_HEIGHT: 150,
  LONG_IMAGE_HEIGHT: 200,
  SQUARE_CONTAINER_FACTOR: 0.7,
  RATIO_CONTAINER_FACTOR: 0.8,
  FREE_CONTAINER_FACTOR: 0.9,
  PREVIEW_SIZE: 150,
  DIALOG_DELAY: 100,
  RESET_DELAY: 100,
}

const props = defineProps<{
  modelValue?: string
  buttonType?: 'primary' | 'success' | 'warning' | 'danger' | 'info'
  buttonSize?: 'large' | 'default' | 'small'
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'crop', value: string): void
}>()

const { t } = useI18n()
const dialogVisible = ref(false)
const imageSrc = ref('')
const selectedRatio = ref('1:1')
const cropperRef = ref<any>(null)
const cropPreviewUrl = ref<string>('')
const cropResult = ref<any>(null)
const cropAreaRef = ref<HTMLElement | null>(null)
const imageDimensions = ref({ width: 0, height: 0 })
const currentRotation = ref(0)
const cropAreaSize = ref({ width: 400, height: 300 })

onMounted(() => {
  updateCropAreaSize()
})

const updateCropAreaSize = () => {
  if (cropAreaRef.value) {
    const rect = cropAreaRef.value.getBoundingClientRect()
    cropAreaSize.value = {
      width: rect.width,
      height: rect.height,
    }
  }
}

const defaultSize = computed(() => {
  if (!imageDimensions.value.width || !imageDimensions.value.height) {
    return { width: 250, height: 250 }
  }

  const containerWidth = cropAreaSize.value.width
  const containerHeight = cropAreaSize.value.height

  if (selectedRatio.value === '1:1') {
    const size = Math.min(
      containerWidth * CONSTANTS.SQUARE_CONTAINER_FACTOR,
      containerHeight * CONSTANTS.SQUARE_CONTAINER_FACTOR,
      CONSTANTS.MAX_SQUARE_SIZE,
    )
    return { width: size, height: size }
  } else if (selectedRatio.value === '4:3') {
    const width = Math.min(
      containerWidth * CONSTANTS.RATIO_CONTAINER_FACTOR,
      CONSTANTS.MAX_4_3_WIDTH,
    )
    return { width, height: (width * 3) / 4 }
  } else if (selectedRatio.value === 'free') {
    const rotation = currentRotation.value % 360
    let imgWidth = imageDimensions.value.width
    let imgHeight = imageDimensions.value.height

    if (rotation === 90 || rotation === 270) {
      ;[imgWidth, imgHeight] = [imgHeight, imgWidth]
    }

    const imgRatio = imgWidth / imgHeight
    const containerRatio = containerWidth / containerHeight

    if (imgRatio > containerRatio) {
      const width = Math.min(
        containerWidth * CONSTANTS.FREE_CONTAINER_FACTOR,
        CONSTANTS.MAX_FREE_WIDTH,
      )
      const height = Math.max(
        Math.min(width / imgRatio, containerHeight * 0.8),
        CONSTANTS.MIN_HEIGHT,
      )
      return { width, height }
    } else {
      const height = CONSTANTS.LONG_IMAGE_HEIGHT
      const width = Math.max(
        Math.min(height * imgRatio, containerWidth * CONSTANTS.RATIO_CONTAINER_FACTOR),
        200,
      )
      return { width, height }
    }
  } else {
    const size = Math.min(
      containerWidth * CONSTANTS.SQUARE_CONTAINER_FACTOR,
      containerHeight * CONSTANTS.SQUARE_CONTAINER_FACTOR,
      CONSTANTS.MAX_SQUARE_SIZE,
    )
    return { width: size, height: size }
  }
})

const defaultPosition = computed(() => {
  if (!imageDimensions.value.width || !imageDimensions.value.height) {
    return { left: 0, top: 0 }
  }

  const containerWidth = cropAreaSize.value.width
  const containerHeight = cropAreaSize.value.height
  const size = defaultSize.value

  return {
    left: (containerWidth - size.width) / 2,
    top: (containerHeight - size.height) / 2,
  }
})

const stencilProps = computed(() => {
  return {
    aspectRatio: selectedRatio.value === '1:1' ? 1 : selectedRatio.value === '4:3' ? 4 / 3 : NaN,
    movable: true,
    resizable: true,
    handlers: {
      north: true,
      south: true,
      east: true,
      west: true,
      northEast: true,
      northWest: true,
      southEast: true,
      southWest: true,
    },
    lines: {
      north: true,
      south: true,
      east: true,
      west: true,
    },
    class: 'custom-stencil',
  }
})

const stencilComponent = computed(() => {
  return undefined
})

const resizeImageOptions = computed(() => {
  return {
    adjustStencil: false,
    touch: {
      passive: true,
    },
    mouse: false,
    autoZoom: false,
  }
})

const moveImageOptions = computed(() => {
  return {
    touch: {
      passive: true,
    },
    mouse: true,
  }
})

const interactionOptions = computed(() => {
  return {
    wheel: false,
    touch: {
      passive: true,
    },
  }
})

const imageRestriction = computed(() => {
  return 'fit-area'
})

const openDialog = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'

  input.onchange = (e) => {
    const target = e.target as HTMLInputElement
    if (target.files && target.files[0]) {
      const file = target.files[0]
      const reader = new FileReader()

      reader.onload = (event) => {
        const img = new Image()

        img.onload = () => {
          const naturalWidth = img.width
          const naturalHeight = img.height

          imageDimensions.value = {
            width: naturalWidth,
            height: naturalHeight,
          }

          currentRotation.value = 0
          imageSrc.value = event.target?.result as string
          updateCropAreaSize()
          dialogVisible.value = true
        }

        img.src = event.target?.result as string
      }

      reader.readAsDataURL(file)
    }
  }

  input.click()
}

const setupWheelZoom = () => {
  if (!cropAreaRef.value) return

  const cropperElement = cropAreaRef.value.querySelector('.custom-cropper') as HTMLElement | null
  if (!cropperElement) return

  cropperElement.removeEventListener('wheel', handleWheelZoom as EventListener)
  cropperElement.addEventListener('wheel', handleWheelZoom as EventListener, { passive: false })
}

const handleWheelZoom = (e: WheelEvent) => {
  if (!cropperRef.value) return

  e.preventDefault()

  const delta = e.deltaY || e.detail
  const zoomFactor = delta > 0 ? 0.9 : 1.1

  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  const center = {
    x: e.clientX - rect.left,
    y: e.clientY - rect.top,
  }

  try {
    cropperRef.value.zoom(zoomFactor, center)
    updatePreview()
  } catch {
    // Silent catch
  }
}

const updatePreview = () => {
  if (!cropperRef.value) return

  try {
    const result = cropperRef.value.getResult({
      canvas: true,
    })

    if (result && typeof result === 'object' && 'canvas' in result) {
      cropResult.value = result

      const canvas = result.canvas
      if (canvas) {
        const previewCanvas = document.createElement('canvas')

        const ratio = Math.min(
          CONSTANTS.PREVIEW_SIZE / canvas.width,
          CONSTANTS.PREVIEW_SIZE / canvas.height,
        )
        previewCanvas.width = canvas.width * ratio
        previewCanvas.height = canvas.height * ratio

        const ctx = previewCanvas.getContext('2d')
        if (ctx) {
          ctx.fillStyle = '#fff'
          ctx.fillRect(0, 0, previewCanvas.width, previewCanvas.height)

          ctx.drawImage(
            canvas,
            0,
            0,
            canvas.width,
            canvas.height,
            0,
            0,
            previewCanvas.width,
            previewCanvas.height,
          )

          cropPreviewUrl.value = previewCanvas.toDataURL('image/png', 0.8)
        }
      }
    }
  } catch {
    // Silent catch
  }
}

const handleClose = () => {
  dialogVisible.value = false
  imageSrc.value = ''
  selectedRatio.value = '1:1'
  cropResult.value = null
  cropPreviewUrl.value = ''
  imageDimensions.value = { width: 0, height: 0 }
  currentRotation.value = 0
}

const onCropChange = (value: any) => {
  if (value && typeof value === 'object') {
    updatePreview()
  }
}

const handleCrop = async () => {
  if (!cropperRef.value || !imageSrc.value) {
    return
  }

  try {
    const result = cropperRef.value.getResult({
      canvas: true,
    })

    if (result && typeof result === 'object' && 'canvas' in result) {
      const canvas = result.canvas
      if (canvas) {
        try {
          const blob = await new Promise<Blob | null>((resolve) => {
            canvas.toBlob(resolve, 'image/png', 0.8)
          })

          if (blob) {
            const file = new File([blob], 'cropped-image.png', { type: 'image/png' })
            const { uploadFile } = await import('@/api/upload')
            const uploadResult = await uploadFile(file)

            emit('update:modelValue', uploadResult.url)
            emit('crop', uploadResult.url)
            handleClose()
          }
        } catch (error) {
          console.error('Error uploading image:', error)
        }
      }
    }
  } catch (error) {
    console.error('Error cropping image:', error)
  }
}

const rotate = async (degrees: number) => {
  if (!cropperRef.value || !imageSrc.value) return

  try {
    currentRotation.value = (currentRotation.value + degrees) % 360
    if (currentRotation.value < 0) currentRotation.value += 360

    cropperRef.value.rotate(degrees)
    await nextTick()

    setTimeout(() => {
      if (cropperRef.value) {
        cropperRef.value.refresh()
        updatePreview()
      }
    }, 50)
  } catch (error) {
    console.error('Error rotating image:', error)
  }
}

const reset = () => {
  handleClose()
  setTimeout(() => {
    openDialog()
  }, CONSTANTS.DIALOG_DELAY)
}

const resetCrop = () => {
  if (cropperRef.value && imageSrc.value) {
    try {
      currentRotation.value = 0

      const tempImageSrc = imageSrc.value
      imageSrc.value = ''

      nextTick(() => {
        imageSrc.value = tempImageSrc
        updateCropAreaSize()

        setTimeout(() => {
          if (cropperRef.value) {
            cropperRef.value.refresh()
            updatePreview()
          }
        }, CONSTANTS.RESET_DELAY)
      })
    } catch (error) {
      console.error('Error resetting crop:', error)
    }
  }
}

watch(selectedRatio, (newValue, oldValue) => {
  if (newValue === 'rotateLeft') {
    rotate(-90)
    selectedRatio.value = oldValue
    return
  } else if (newValue === 'rotateRight') {
    rotate(90)
    selectedRatio.value = oldValue
    return
  } else if (newValue === 'reset') {
    resetCrop()
    selectedRatio.value = oldValue
    return
  }

  if (cropperRef.value) {
    try {
      cropperRef.value.refresh()
      nextTick(() => {
        updatePreview()
      })
    } catch (error) {
      console.error('Error updating cropper:', error)
    }
  }
})

watch(dialogVisible, (newValue) => {
  if (newValue && imageSrc.value) {
    nextTick(() => {
      updateCropAreaSize()

      setTimeout(() => {
        updateCropAreaSize()
        setupWheelZoom()
        if (cropperRef.value) {
          cropperRef.value.refresh()
          updatePreview()
        }
      }, 200)
    })
  } else {
    if (cropAreaRef.value) {
      const cropperElement = cropAreaRef.value.querySelector('.custom-cropper') as HTMLElement | null
      if (cropperElement) {
        cropperElement.removeEventListener('wheel', handleWheelZoom as EventListener)
        cropperElement.removeAttribute('data-wheel-listener')
      }
    }
  }
})

const onCropperReady = () => {
  if (
    !cropperRef.value ||
    !cropAreaRef.value ||
    !imageDimensions.value.width ||
    !imageDimensions.value.height
  ) {
    return
  }

  nextTick(() => {
    if (!cropAreaRef.value) return
    const containerRect = cropAreaRef.value.getBoundingClientRect()
    const containerW = containerRect.width
    const containerH = containerRect.height

    let imageW = imageDimensions.value.width
    let imageH = imageDimensions.value.height

    const rotation = currentRotation.value % 360
    if (rotation === 90 || rotation === 270) {
      ;[imageW, imageH] = [imageH, imageW]
    }

    const imageScale = Math.min(containerW / imageW, containerH / imageH)

    let stencilWidthContainer, stencilHeightContainer

    if (selectedRatio.value === '1:1') {
      const size = Math.min(
        containerW * CONSTANTS.SQUARE_CONTAINER_FACTOR,
        containerH * CONSTANTS.SQUARE_CONTAINER_FACTOR,
        CONSTANTS.MAX_SQUARE_SIZE,
      )
      stencilWidthContainer = size
      stencilHeightContainer = size
    } else if (selectedRatio.value === '4:3') {
      stencilWidthContainer = Math.min(
        containerW * CONSTANTS.RATIO_CONTAINER_FACTOR,
        CONSTANTS.MAX_4_3_WIDTH,
      )
      stencilHeightContainer = (stencilWidthContainer * 3) / 4
    } else {
      if (imageW / imageH > containerW / containerH) {
        stencilWidthContainer = Math.min(
          containerW * CONSTANTS.FREE_CONTAINER_FACTOR,
          CONSTANTS.MAX_FREE_WIDTH,
        )
        stencilHeightContainer = Math.max(
          Math.min(stencilWidthContainer / (imageW / imageH), containerH * 0.8),
          CONSTANTS.MIN_HEIGHT,
        )
      } else {
        stencilWidthContainer = Math.min(
          containerW * CONSTANTS.RATIO_CONTAINER_FACTOR,
          CONSTANTS.MAX_LONG_WIDTH,
        )
        stencilHeightContainer = CONSTANTS.LONG_IMAGE_HEIGHT
      }
    }

    const stencilWidth = stencilWidthContainer / imageScale
    const stencilHeight = stencilHeightContainer / imageScale

    const leftContainer = (containerW - stencilWidthContainer) / 2
    const topContainer = (containerH - stencilHeightContainer) / 2

    const left = leftContainer / imageScale
    const top = topContainer / imageScale

    cropperRef.value.setCoordinates({
      left,
      top,
      width: stencilWidth,
      height: stencilHeight,
    })

    updatePreview()
  })
}

onUnmounted(() => {
  if (cropAreaRef.value) {
    const cropperElement = cropAreaRef.value.querySelector('.custom-cropper') as HTMLElement | null
    if (cropperElement) {
      cropperElement.removeEventListener('wheel', handleWheelZoom as EventListener)
    }
  }
})
</script>

<style scoped lang="scss">
.image-cropper-container {
  padding: 20px 0;
  overflow: hidden;
}

.main-content {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;

  @media (max-width: 768px) {
    flex-direction: column;
  }
}

.crop-area {
  flex: 1;
  min-width: 300px;
  height: 300px;
  overflow: hidden;
  position: relative;
  background-color: #f5f5f5;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
}

.custom-cropper {
  width: 100%;
  height: 100%;
}

:deep(.vue-advanced-cropper) {
  width: 100%;
  height: 100%;
  touch-action: none;

  .cropper {
    &__wrapper {
      width: 100%;
      height: 100%;
      touch-action: none;
      position: relative;
      overflow: hidden;
    }

    &__image {
      cursor: grab;
      max-width: none !important;
      max-height: none !important;

      &:active {
        cursor: grabbing;
      }
    }

    &__stencil {
      border: 2px solid #409eff;
      background: rgba(64, 158, 255, 0.1);
    }

    &__resize-handler {
      background: #409eff;
      border: 2px solid #fff;
      border-radius: 50%;
      width: 14px;
      height: 14px;
      box-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
    }

    &__draggable-area {
      cursor: move;
      background: transparent;
    }

    &__handler {
      opacity: 0.8;

      &:hover {
        opacity: 1;
      }
    }

    &__line {
      background-color: rgba(64, 158, 255, 0.5);
    }
  }
}

.preview-area {
  width: 150px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex-shrink: 0;

  h4 {
    font-size: 14px;
    color: #606266;
    margin: 0;
  }

  .preview-container {
    width: 100%;
    aspect-ratio: 1;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    position: relative;
    background-color: #f5f5f5;
  }

  .preview-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .preview-placeholder {
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px dashed #e0e0e0;
    border-radius: 4px;
    font-size: 12px;
    color: #909399;
    background-color: #f5f5f5;
  }
}

.aspect-ratio-selector {
  margin-top: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

:deep(.el-radio-group) {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  gap: 10px;
}

.footer-left,
.footer-right {
  display: flex;
  align-items: center;
}

.footer-right {
  gap: 10px;
}
</style>
